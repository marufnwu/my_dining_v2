<?php

namespace App\Services;

use App\Enums\MessUserStatus;
use App\Enums\NotificationTemplate;
use App\Helpers\Pipeline;
use App\Models\Mess;
use App\Models\MessUser;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class NotificationService
{
    protected string $fcmServerKey;
    protected string $fcmUrl = 'https://fcm.googleapis.com/fcm/send';

    public function __construct()
    {
        $this->fcmServerKey = config('services.firebase.server_key');
    }

    /**
     * Send notification to specific user(s)
     */
    public function sendToUsers(array|Collection $users, array|NotificationTemplate $notification, array $params = [], ?Mess $mess = null): Pipeline
    {
        $mess = $mess ?? app()->getMess();
        if (!$mess) {
            return Pipeline::error('No mess context available');
        }

        if ($notification instanceof NotificationTemplate) {
            $template = $notification->getTemplate();
            $notification = [
                'title' => $this->replaceParams($template['title'], $params),
                'body' => $this->replaceParams($template['body'], $params),
                'type' => $notification->value
            ];
        }

        if ($notification['type'] ?? null === null) {
            $notification['type'] = 'custom_notification';
        }

        $users = is_array($users) ? collect($users) : $users;

        // Get FCM tokens for all valid users
        $tokens = $users->map(fn($user) => $user->fcm_token)
            ->filter()
            ->values()
            ->toArray();

        if (!empty($tokens)) {
            $this->sendToFcm($tokens, $notification);
        }

        // Create notification records
        foreach ($users as $user) {
            Notification::create([
                'mess_id' => $mess->id,
                'user_id' => $user->id,
                'title' => $notification['title'],
                'body' => $notification['body'],
                'type' => $notification['type'],
                'data' => $notification['extra_data'] ?? null,
                'is_broadcast' => false
            ]);
        }

        return Pipeline::success();
    }

    /**
     * Send notification to specific MessUser(s)
     */
    public function sendToMessUsers(array|Collection $messUsers, array|NotificationTemplate $notification, array $params = [], ?Mess $mess = null): Pipeline
    {
        $mess = $mess ?? app()->getMess();
        if (!$mess) {
            return Pipeline::error('No mess context available');
        }

        $messUsers = is_array($messUsers) ? collect($messUsers) : $messUsers;

        // Filter active mess users and get their users
        $users = $messUsers->filter(function($messUser) {
            return $messUser->status === MessUserStatus::Active && !$messUser->left_at;
        })->map(fn($messUser) => $messUser->user);

        return $this->sendToUsers($users, $notification, $params, $mess);
    }

    /**
     * Send notification to all active members of a mess
     */
    public function sendToAllMessMembers(array|NotificationTemplate $notification, array $params = [], ?Mess $mess = null): Pipeline
    {
        $mess = $mess ?? app()->getMess();
        if (!$mess) {
            return Pipeline::error('No mess context available');
        }

        $messUsers = $mess->messUsers()
            ->where('status', MessUserStatus::Active)
            ->whereNull('left_at')
            ->with('user')
            ->get();

        return $this->sendToMessUsers($messUsers, $notification, $params, $mess);
    }

    /**
     * Send notification to mess members with specific roles
     */
    public function sendToRole(string|array $roles, array|NotificationTemplate $notification, array $params = [], ?Mess $mess = null): Pipeline
    {
        $mess = $mess ?? app()->getMess();
        if (!$mess) {
            return Pipeline::error('No mess context available');
        }

        $roles = is_array($roles) ? $roles : [$roles];

        $messUsers = $mess->messUsers()
            ->where('status', MessUserStatus::Active)
            ->whereNull('left_at')
            ->whereHas('role', function($q) use ($roles) {
                $q->whereIn('role', $roles);
            })
            ->with(['user', 'role'])
            ->get();

        return $this->sendToMessUsers($messUsers, $notification, $params, $mess);
    }

    /**
     * Send a custom notification
     */
    public function sendCustomNotification(array $recipients, string $title, string $body, array $extraData = [], ?Mess $mess = null): Pipeline
    {
        return $this->sendToUsers($recipients, [
            'title' => $title,
            'body' => $body,
            'type' => 'custom_notification',
            'extra_data' => $extraData
        ], [], $mess);
    }

    public function getUserNotifications(User $user, array $filters = []): Pipeline
    {
        try {
            $mess = app()->getMess();
            if (!$mess) {
                return Pipeline::error('No mess context available');
            }

            // Check if user is an active member of the mess
            $isActiveMember = $mess->messUsers()
                ->where('user_id', $user->id)
                ->where('status', MessUserStatus::Active)
                ->whereNull('left_at')
                ->exists();

            if (!$isActiveMember) {
                return Pipeline::error('User is not an active member of the mess');
            }

            $query = Notification::query()
                ->where('mess_id', $mess->id)
                ->forUser($user->id)
                ->with(['user']);

            if (isset($filters['type'])) {
                $query->where('type', $filters['type']);
            }

            if (isset($filters['unread_only']) && $filters['unread_only']) {
                $query->unread();
            }

            $notifications = $query->orderBy('created_at', 'desc')
                ->paginate($filters['per_page'] ?? 15);

            return Pipeline::success($notifications);
        } catch (\Exception $e) {
            Log::error('Failed to fetch notifications: ' . $e->getMessage());
            return Pipeline::error('Failed to fetch notifications');
        }
    }

    public function markAsRead(Notification $notification): Pipeline
    {
        try {
            // Verify the notification belongs to the current mess
            if ($notification->mess_id !== app()->getMess()?->id) {
                return Pipeline::error('Notification does not belong to the current mess');
            }

            $notification->markAsRead();
            return Pipeline::success($notification);
        } catch (\Exception $e) {
            return Pipeline::error('Failed to mark notification as read');
        }
    }

    /**
     * Replace parameters in notification template text
     */
    protected function replaceParams(string $text, array $params): string
    {
        foreach ($params as $key => $value) {
            $text = str_replace('{' . $key . '}', $value, $text);
        }
        return $text;
    }

    /**
     * Send notification to FCM
     */
    protected function sendToFcm($tokens, array $data): void
    {
        try {
            $fcmData = [
                'notification' => [
                    'title' => $data['title'],
                    'body' => $data['body'],
                ],
                'data' => [
                    'type' => $data['type'],
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                    'mess_id' => (string) app()->getMess()?->id,
                ] + ($data['extra_data'] ?? []),
                'priority' => 'high',
            ];

            if (is_array($tokens)) {
                $fcmData['registration_ids'] = $tokens;
            } else {
                $fcmData['to'] = $tokens;
            }

            Http::withHeaders([
                'Authorization' => 'key=' . $this->fcmServerKey,
                'Content-Type' => 'application/json',
            ])->post($this->fcmUrl, $fcmData);
        } catch (\Exception $e) {
            Log::error('FCM send failed: ' . $e->getMessage());
        }
    }
}
