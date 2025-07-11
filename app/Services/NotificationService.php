<?php

namespace App\Services;

use App\Enums\MessUserStatus;
use App\Enums\NotificationCategory;
use App\Enums\NotificationPriority;
use App\Enums\NotificationTemplate;
use App\Helpers\Pipeline;
use App\Models\Mess;
use App\Models\MessUser;
use App\Models\Notification;
use App\Models\User;
use Carbon\Carbon;
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
        // Add a default value or empty string to avoid type errors
        $this->fcmServerKey = config('services.firebase.server_key', '') ?? '';
    }

    /**
     * Send notification using template
     */
    public function sendTemplate(
        NotificationTemplate $template,
        array $recipients,
        array $params = [],
        ?Mess $mess = null,
        array $options = []
    ): Pipeline {
        $templateData = $template->getTemplate();

        // Extract options
        $category = $templateData['category'] ?? NotificationCategory::SYSTEM;
        $priority = $templateData['priority'] ?? NotificationPriority::NORMAL;
        $actionData = $templateData['action_data'] ?? null;
        $isActionable = $templateData['is_actionable'] ?? false;
        $isDismissible = $templateData['is_dismissible'] ?? true;
        $expiresAt = isset($templateData['expires_at']) ? Carbon::parse($templateData['expires_at']) : null;

        return $this->send(
            $recipients,
            $this->replaceParams($templateData['title'], $params),
            $this->replaceParams($templateData['body'], $params),
            $template->value,
            $category,
            $priority,
            $params,
            $actionData,
            $isActionable,
            $isDismissible,
            $options['icon'] ?? null,
            $options['color'] ?? null,
            $options['imageUrl'] ?? null,
            $expiresAt,
            $options['scheduledAt'] ?? null,
            $mess
        );
    }

    /**
     * Main notification sending method
     */
    public function send(
        array|Collection|string $recipients,
        string $title,
        string $body,
        string $type = 'custom',
        NotificationCategory $category = NotificationCategory::SYSTEM,
        NotificationPriority $priority = NotificationPriority::NORMAL,
        array $data = [],
        ?array $actionData = null,
        bool $isActionable = false,
        bool $isDismissible = true,
        ?string $icon = null,
        ?string $color = null,
        ?string $imageUrl = null,
        ?Carbon $expiresAt = null,
        ?Carbon $scheduledAt = null,
        ?Mess $mess = null
    ): Pipeline {
        try {
            $mess = $mess ?? app()->getMess();
            if (!$mess) {
                return Pipeline::error('No mess context available');
            }

            // Parse recipients
            $recipientData = $this->parseRecipients($recipients, $mess);
            if ($recipientData['users']->isEmpty()) {
                return Pipeline::error('No valid recipients found');
            }

            // Set notification properties
            $notificationData = [
                'mess_id' => $mess->id,
                'title' => $title,
                'body' => $body,
                'type' => $type,
                'category' => $category,
                'priority' => $priority,
                'data' => $data,
                'action_data' => $actionData,
                'is_actionable' => $isActionable,
                'is_dismissible' => $isDismissible,
                'icon' => $icon ?? $category->getIcon(),
                'color' => $color ?? $priority->getColor(),
                'image_url' => $imageUrl,
                'expires_at' => $expiresAt,
                'scheduled_at' => $scheduledAt,
                'source' => auth()->user()?->name ?? 'System',
                'delivery_channels' => ['fcm', 'database'],
                'is_broadcast' => $recipientData['is_broadcast'],
            ];

            // Create notifications for each recipient
            $notifications = collect();
            foreach ($recipientData['users'] as $user) {
                $notification = Notification::create(array_merge($notificationData, [
                    'user_id' => $user->id,
                    'is_broadcast' => false, // Individual records are not broadcast
                ]));
                $notifications->push($notification);
            }

            // Send FCM notifications if not scheduled
            if (!$scheduledAt || $scheduledAt->isPast()) {
                $this->sendFcmNotifications($recipientData['users'], $notificationData);
            }

            return Pipeline::success($notifications);
        } catch (\Exception $e) {
            Log::error('Failed to send notification: ' . $e->getMessage());
            return Pipeline::error('Failed to send notification: ' . $e->getMessage());
        }
    }

    /**
     * Send to specific users
     */
    public function sendToUsers(
        array|Collection $users,
        string $title,
        string $body,
        string $type = 'custom',
        array $options = []
    ): Pipeline {
        $category = $options['category'] ?? NotificationCategory::SYSTEM;
        $priority = $options['priority'] ?? NotificationPriority::NORMAL;

        return $this->send(
            $users,
            $title,
            $body,
            $type,
            $category,
            $priority,
            $options['data'] ?? [],
            $options['actionData'] ?? null,
            $options['isActionable'] ?? false,
            $options['isDismissible'] ?? true,
            $options['icon'] ?? null,
            $options['color'] ?? null,
            $options['imageUrl'] ?? null,
            $options['expiresAt'] ?? null,
            $options['scheduledAt'] ?? null,
            $options['mess'] ?? null
        );
    }

    /**
     * Send to specific mess users
     */
    public function sendToMessUsers(
        array|Collection $messUsers,
        string $title,
        string $body,
        string $type = 'custom',
        array $options = []
    ): Pipeline {
        $users = collect($messUsers)->map(fn($messUser) => $messUser->user);
        return $this->sendToUsers($users, $title, $body, $type, $options);
    }

    /**
     * Send to all active mess members
     */
    public function sendToAllMembers(
        string $title,
        string $body,
        string $type = 'broadcast',
        ?Mess $mess = null,
        array $options = []
    ): Pipeline {
        $category = $options['category'] ?? NotificationCategory::ANNOUNCEMENT;
        $priority = $options['priority'] ?? NotificationPriority::NORMAL;

        return $this->send(
            'all_members',
            $title,
            $body,
            $type,
            $category,
            $priority,
            $options['data'] ?? [],
            $options['actionData'] ?? null,
            $options['isActionable'] ?? false,
            $options['isDismissible'] ?? true,
            $options['icon'] ?? null,
            $options['color'] ?? null,
            $options['imageUrl'] ?? null,
            $options['expiresAt'] ?? null,
            $options['scheduledAt'] ?? null,
            $mess
        );
    }

    /**
     * Send to users with specific roles
     */
    public function sendToRoles(
        array|string $roles,
        string $title,
        string $body,
        string $type = 'role_notification',
        ?Mess $mess = null,
        array $options = []
    ): Pipeline {
        $roles = is_string($roles) ? [$roles] : $roles;
        $category = $options['category'] ?? NotificationCategory::MESS_MANAGEMENT;
        $priority = $options['priority'] ?? NotificationPriority::NORMAL;

        return $this->send(
            ['roles' => $roles],
            $title,
            $body,
            $type,
            $category,
            $priority,
            $options['data'] ?? [],
            $options['actionData'] ?? null,
            $options['isActionable'] ?? false,
            $options['isDismissible'] ?? true,
            $options['icon'] ?? null,
            $options['color'] ?? null,
            $options['imageUrl'] ?? null,
            $options['expiresAt'] ?? null,
            $options['scheduledAt'] ?? null,
            $mess
        );
    }

    /**
     * Send to admins only
     */
    public function sendToAdmins(
        string $title,
        string $body,
        string $type = 'admin_notification',
        ?Mess $mess = null,
        array $options = []
    ): Pipeline {
        $category = $options['category'] ?? NotificationCategory::SYSTEM;
        $priority = $options['priority'] ?? NotificationPriority::HIGH;

        return $this->send(
            'admins',
            $title,
            $body,
            $type,
            $category,
            $priority,
            $options['data'] ?? [],
            $options['actionData'] ?? null,
            $options['isActionable'] ?? false,
            $options['isDismissible'] ?? true,
            $options['icon'] ?? null,
            $options['color'] ?? null,
            $options['imageUrl'] ?? null,
            $options['expiresAt'] ?? null,
            $options['scheduledAt'] ?? null,
            $mess
        );
    }

    /**
     * Schedule a notification
     */
    public function schedule(
        Carbon $scheduledAt,
        array|Collection|string $recipients,
        string $title,
        string $body,
        string $type = 'scheduled',
        array $options = []
    ): Pipeline {
        $category = $options['category'] ?? NotificationCategory::SYSTEM;
        $priority = $options['priority'] ?? NotificationPriority::NORMAL;

        return $this->send(
            $recipients,
            $title,
            $body,
            $type,
            $category,
            $priority,
            $options['data'] ?? [],
            $options['actionData'] ?? null,
            $options['isActionable'] ?? false,
            $options['isDismissible'] ?? true,
            $options['icon'] ?? null,
            $options['color'] ?? null,
            $options['imageUrl'] ?? null,
            $options['expiresAt'] ?? null,
            $scheduledAt,
            $options['mess'] ?? null
        );
    }

    /**
     * Send actionable notification
     */
    public function sendActionable(
        array|Collection|string $recipients,
        string $title,
        string $body,
        array $actions,
        string $type = 'actionable',
        array $options = []
    ): Pipeline {
        $category = $options['category'] ?? NotificationCategory::SYSTEM;
        $priority = $options['priority'] ?? NotificationPriority::NORMAL;

        return $this->send(
            $recipients,
            $title,
            $body,
            $type,
            $category,
            $priority,
            $options['data'] ?? [],
            $actions,
            true,
            $options['isDismissible'] ?? true,
            $options['icon'] ?? null,
            $options['color'] ?? null,
            $options['imageUrl'] ?? null,
            $options['expiresAt'] ?? null,
            $options['scheduledAt'] ?? null,
            $options['mess'] ?? null
        );
    }

    /**
     * Process scheduled notifications
     */
    public function processScheduled(): Pipeline
    {
        try {
            $scheduledNotifications = Notification::query()
                ->scheduled()
                ->due()
                ->where('is_delivered', false)
                ->get();

            foreach ($scheduledNotifications as $notification) {
                $this->deliverScheduledNotification($notification);
            }

            return Pipeline::success(count($scheduledNotifications));
        } catch (\Exception $e) {
            Log::error('Failed to process scheduled notifications: ' . $e->getMessage());
            return Pipeline::error('Failed to process scheduled notifications');
        }
    }

    /**
     * Clean up expired notifications
     */
    public function cleanupExpired(): Pipeline
    {
        try {
            $expiredCount = Notification::query()
                ->expired()
                ->delete();

            return Pipeline::success($expiredCount);
        } catch (\Exception $e) {
            Log::error('Failed to cleanup expired notifications: ' . $e->getMessage());
            return Pipeline::error('Failed to cleanup expired notifications');
        }
    }

    /**
     * Get user notifications with rich filtering
     */
    public function getUserNotifications(User $user, array $filters = []): Pipeline
    {
        try {
            $mess = app()->getMess();
            if (!$mess) {
                return Pipeline::error('No mess context available');
            }

            // Verify user is active member
            if (!$this->isActiveMember($user, $mess)) {
                return Pipeline::error('User is not an active member');
            }

            $query = Notification::query()
                ->where('mess_id', $mess->id)
                ->forUser($user->id)
                ->notExpired()
                ->with(['user', 'mess']);

            // Apply filters
            if (isset($filters['category'])) {
                $query->byCategory(NotificationCategory::from($filters['category']));
            }

            if (isset($filters['priority'])) {
                $query->byPriority(NotificationPriority::from($filters['priority']));
            }

            if (isset($filters['type'])) {
                $query->where('type', $filters['type']);
            }

            if (isset($filters['unread_only']) && $filters['unread_only']) {
                $query->unread();
            }

            if (isset($filters['actionable_only']) && $filters['actionable_only']) {
                $query->actionable();
            }

            if (isset($filters['high_priority_only']) && $filters['high_priority_only']) {
                $query->highPriority();
            }

            $notifications = $query->orderBy('priority', 'desc')
                ->orderBy('created_at', 'desc')
                ->paginate($filters['per_page'] ?? 20);

            return Pipeline::success($notifications);
        } catch (\Exception $e) {
            Log::error('Failed to fetch notifications: ' . $e->getMessage());
            return Pipeline::error('Failed to fetch notifications');
        }
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(Notification $notification): Pipeline
    {
        try {
            if ($notification->mess_id !== app()->getMess()?->id) {
                return Pipeline::error('Invalid notification');
            }

            $notification->markAsRead();
            return Pipeline::success($notification);
        } catch (\Exception $e) {
            return Pipeline::error('Failed to mark as read');
        }
    }

    /**
     * Mark all notifications as read for user
     */
    public function markAllAsRead(User $user): Pipeline
    {
        try {
            $mess = app()->getMess();
            $count = Notification::query()
                ->where('mess_id', $mess->id)
                ->forUser($user->id)
                ->unread()
                ->update(['read_at' => now()]);

            return Pipeline::success($count);
        } catch (\Exception $e) {
            return Pipeline::error('Failed to mark all as read');
        }
    }

    /**
     * Get notification statistics
     */
    public function getStats(?Mess $mess = null): Pipeline
    {
        try {
            $mess = $mess ?? app()->getMess();

            $stats = [
                'total' => Notification::where('mess_id', $mess->id)->count(),
                'unread' => Notification::where('mess_id', $mess->id)->unread()->count(),
                'high_priority' => Notification::where('mess_id', $mess->id)->highPriority()->count(),
                'actionable' => Notification::where('mess_id', $mess->id)->actionable()->count(),
                'by_category' => Notification::where('mess_id', $mess->id)
                    ->selectRaw('category, COUNT(*) as count')
                    ->groupBy('category')
                    ->pluck('count', 'category'),
                'by_priority' => Notification::where('mess_id', $mess->id)
                    ->selectRaw('priority, COUNT(*) as count')
                    ->groupBy('priority')
                    ->pluck('count', 'priority'),
            ];

            return Pipeline::success($stats);
        } catch (\Exception $e) {
            return Pipeline::error('Failed to get stats');
        }
    }

    /**
     * Parse recipients into users collection
     */
    private function parseRecipients($recipients, Mess $mess): array
    {
        $users = collect();
        $isBroadcast = false;

        if (is_string($recipients)) {
            switch ($recipients) {
                case 'all_members':
                    $users = $this->getAllActiveMembers($mess);
                    $isBroadcast = true;
                    break;
                case 'admins':
                    $users = $this->getAdmins($mess);
                    break;
                default:
                    // Single user by ID or email
                    $user = User::find($recipients) ?? User::where('email', $recipients)->first();
                    if ($user && $this->isActiveMember($user, $mess)) {
                        $users->push($user);
                    }
            }
        } elseif (is_array($recipients)) {
            if (isset($recipients['roles'])) {
                $users = $this->getUsersByRoles($recipients['roles'], $mess);
            } else {
                // Array of user IDs or User objects
                foreach ($recipients as $recipient) {
                    if ($recipient instanceof User) {
                        if ($this->isActiveMember($recipient, $mess)) {
                            $users->push($recipient);
                        }
                    } elseif (is_numeric($recipient)) {
                        $user = User::find($recipient);
                        if ($user && $this->isActiveMember($user, $mess)) {
                            $users->push($user);
                        }
                    }
                }
            }
        } elseif ($recipients instanceof Collection) {
            $users = $recipients->filter(fn($user) => $this->isActiveMember($user, $mess));
        }

        return [
            'users' => $users,
            'is_broadcast' => $isBroadcast,
        ];
    }

    private function getAllActiveMembers(Mess $mess): Collection
    {
        return $mess->messUsers()
            ->where('status', MessUserStatus::Active)
            ->whereNull('left_at')
            ->with('user')
            ->get()
            ->map(fn($messUser) => $messUser->user);
    }

    private function getAdmins(Mess $mess): Collection
    {
        return $mess->messUsers()
            ->where('status', MessUserStatus::Active)
            ->whereNull('left_at')
            ->whereHas('role', fn($q) => $q->where('is_admin', true))
            ->with('user')
            ->get()
            ->map(fn($messUser) => $messUser->user);
    }

    private function getUsersByRoles(array $roles, Mess $mess): Collection
    {
        return $mess->messUsers()
            ->where('status', MessUserStatus::Active)
            ->whereNull('left_at')
            ->whereHas('role', fn($q) => $q->whereIn('role', $roles))
            ->with('user')
            ->get()
            ->map(fn($messUser) => $messUser->user);
    }

    private function isActiveMember(User $user, Mess $mess): bool
    {
        return $mess->messUsers()
            ->where('user_id', $user->id)
            ->where('status', MessUserStatus::Active)
            ->whereNull('left_at')
            ->exists();
    }

    private function sendFcmNotifications(Collection $users, array $notificationData): void
    {
        $tokens = $users->pluck('fcm_token')->filter()->values();

        if ($tokens->isNotEmpty()) {
            $this->sendToFcm($tokens->toArray(), $notificationData);
        }
    }

    private function deliverScheduledNotification(Notification $notification): void
    {
        if ($notification->user && $notification->user->fcm_token) {
            $this->sendToFcm($notification->user->fcm_token, [
                'title' => $notification->title,
                'body' => $notification->body,
                'type' => $notification->type,
                'extra_data' => $notification->data ?? [],
            ]);
        }
    }

    private function replaceParams(string $text, array $params): string
    {
        foreach ($params as $key => $value) {
            $text = str_replace('{' . $key . '}', $value, $text);
        }
        return $text;
    }

    private function sendToFcm($tokens, array $data): void
    {
        // Skip FCM if no server key is configured
        if (empty($this->fcmServerKey)) {
            Log::warning('FCM server key not configured. Skipping FCM notification.');
            return;
        }

        try {
            $fcmData = [
                'notification' => [
                    'title' => $data['title'],
                    'body' => $data['body'],
                ],
                'data' => [
                    'type' => $data['type'],
                    'category' => $data['category']->value ?? 'system',
                    'priority' => $data['priority']->value ?? 'normal',
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                    'mess_id' => (string) $data['mess_id'],
                ] + ($data['data'] ?? []),
                'priority' => 'high',
            ];

            if (is_array($tokens)) {
                $fcmData['registration_ids'] = $tokens;
            } else {
                $fcmData['to'] = $tokens;
            }

            $response = Http::withHeaders([
                'Authorization' => 'key=' . $this->fcmServerKey,
                'Content-Type' => 'application/json',
            ])->post($this->fcmUrl, $fcmData);

            // Log FCM response for debugging
            if (!$response->successful()) {
                Log::error('FCM send failed', [
                    'response' => $response->json(),
                    'status' => $response->status()
                ]);
            }
        } catch (\Exception $e) {
            Log::error('FCM send failed: ' . $e->getMessage());
        }
    }
}
