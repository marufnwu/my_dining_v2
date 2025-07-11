<?php

namespace App\Http\Controllers\Api;

use App\Enums\NotificationCategory;
use App\Enums\NotificationPriority;
use App\Enums\NotificationTemplate;
use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\User;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class NotificationController extends Controller
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Get user notifications with advanced filtering
     */
    public function index(Request $request)
    {
        $filters = $request->validate([
            'category' => ['sometimes', Rule::enum(NotificationCategory::class)],
            'priority' => ['sometimes', Rule::enum(NotificationPriority::class)],
            'type' => 'sometimes|string',
            'unread_only' => 'sometimes|boolean',
            'actionable_only' => 'sometimes|boolean',
            'high_priority_only' => 'sometimes|boolean',
            'per_page' => 'sometimes|integer|min:1|max:100'
        ]);

        $pipeline = $this->notificationService->getUserNotifications(auth()->user(), $filters);
        return $pipeline->toApiResponse();
    }

    /**
     * Get notification statistics
     */
    public function stats()
    {
        $pipeline = $this->notificationService->getStats();
        return $pipeline->toApiResponse();
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(Notification $notification)
    {
        $pipeline = $this->notificationService->markAsRead($notification);
        return $pipeline->toApiResponse();
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead()
    {
        $pipeline = $this->notificationService->markAllAsRead(auth()->user());
        return $pipeline->toApiResponse();
    }

    /**
     * Send custom notification
     */
    public function sendCustom(Request $request)
    {
        $data = $request->validate([
            'recipients' => 'required|array',
            'recipients.*' => 'integer|exists:users,id',
            'title' => 'required|string|max:255',
            'body' => 'required|string|max:1000',
            'type' => 'sometimes|string|max:50',
            'category' => ['sometimes', Rule::enum(NotificationCategory::class)],
            'priority' => ['sometimes', Rule::enum(NotificationPriority::class)],
            'data' => 'sometimes|array',
            'action_data' => 'sometimes|array',
            'is_actionable' => 'sometimes|boolean',
            'expires_at' => 'sometimes|date|after:now',
            'scheduled_at' => 'sometimes|date|after:now',
            'image_url' => 'sometimes|url',
        ]);

        $pipeline = $this->notificationService->send(
            recipients: $data['recipients'],
            title: $data['title'],
            body: $data['body'],
            type: $data['type'] ?? 'custom',
            category: isset($data['category']) ? NotificationCategory::from($data['category']) : NotificationCategory::SYSTEM,
            priority: isset($data['priority']) ? NotificationPriority::from($data['priority']) : NotificationPriority::NORMAL,
            data: $data['data'] ?? [],
            actionData: $data['action_data'] ?? null,
            isActionable: $data['is_actionable'] ?? false,
            imageUrl: $data['image_url'] ?? null,
            expiresAt: isset($data['expires_at']) ? Carbon::parse($data['expires_at']) : null,
            scheduledAt: isset($data['scheduled_at']) ? Carbon::parse($data['scheduled_at']) : null
        );

        return $pipeline->toApiResponse();
    }

    /**
     * Send template notification
     */
    public function sendTemplate(Request $request)
    {
        $data = $request->validate([
            'template' => ['required', Rule::enum(NotificationTemplate::class)],
            'recipients' => 'required|array',
            'recipients.*' => 'integer|exists:users,id',
            'params' => 'sometimes|array',
            'scheduled_at' => 'sometimes|date|after:now',
        ]);

        $pipeline = $this->notificationService->sendTemplate(
            template: NotificationTemplate::from($data['template']),
            recipients: $data['recipients'],
            params: $data['params'] ?? [],
            options: isset($data['scheduled_at']) ? ['scheduledAt' => Carbon::parse($data['scheduled_at'])] : []
        );

        return $pipeline->toApiResponse();
    }

    /**
     * Send to all members
     */
    public function sendToAllMembers(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string|max:1000',
            'type' => 'sometimes|string|max:50',
            'category' => ['sometimes', Rule::enum(NotificationCategory::class)],
            'priority' => ['sometimes', Rule::enum(NotificationPriority::class)],
            'data' => 'sometimes|array',
            'scheduled_at' => 'sometimes|date|after:now',
        ]);

        $pipeline = $this->notificationService->sendToAllMembers(
            title: $data['title'],
            body: $data['body'],
            type: $data['type'] ?? 'broadcast',
            options: [
                'category' => isset($data['category']) ? NotificationCategory::from($data['category']) : NotificationCategory::ANNOUNCEMENT,
                'priority' => isset($data['priority']) ? NotificationPriority::from($data['priority']) : NotificationPriority::NORMAL,
                'data' => $data['data'] ?? [],
                'scheduledAt' => isset($data['scheduled_at']) ? Carbon::parse($data['scheduled_at']) : null
            ]
        );

        return $pipeline->toApiResponse();
    }

    /**
     * Send to specific roles
     */
    public function sendToRoles(Request $request)
    {
        $data = $request->validate([
            'roles' => 'required|array',
            'roles.*' => 'string',
            'title' => 'required|string|max:255',
            'body' => 'required|string|max:1000',
            'type' => 'sometimes|string|max:50',
            'category' => ['sometimes', Rule::enum(NotificationCategory::class)],
            'priority' => ['sometimes', Rule::enum(NotificationPriority::class)],
            'data' => 'sometimes|array',
        ]);

        $pipeline = $this->notificationService->sendToRoles(
            roles: $data['roles'],
            title: $data['title'],
            body: $data['body'],
            type: $data['type'] ?? 'role_notification',
            options: [
                'category' => isset($data['category']) ? NotificationCategory::from($data['category']) : NotificationCategory::MESS_MANAGEMENT,
                'priority' => isset($data['priority']) ? NotificationPriority::from($data['priority']) : NotificationPriority::NORMAL,
                'data' => $data['data'] ?? []
            ]
        );

        return $pipeline->toApiResponse();
    }

    /**
     * Send to admins
     */
    public function sendToAdmins(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string|max:1000',
            'type' => 'sometimes|string|max:50',
            'category' => ['sometimes', Rule::enum(NotificationCategory::class)],
            'priority' => ['sometimes', Rule::enum(NotificationPriority::class)],
            'data' => 'sometimes|array',
        ]);

        $pipeline = $this->notificationService->sendToAdmins(
            title: $data['title'],
            body: $data['body'],
            type: $data['type'] ?? 'admin_notification',
            options: [
                'category' => isset($data['category']) ? NotificationCategory::from($data['category']) : NotificationCategory::SYSTEM,
                'priority' => isset($data['priority']) ? NotificationPriority::from($data['priority']) : NotificationPriority::HIGH,
                'data' => $data['data'] ?? []
            ]
        );

        return $pipeline->toApiResponse();
    }

    /**
     * Send actionable notification
     */
    public function sendActionable(Request $request)
    {
        $data = $request->validate([
            'recipients' => 'required|array',
            'recipients.*' => 'integer|exists:users,id',
            'title' => 'required|string|max:255',
            'body' => 'required|string|max:1000',
            'actions' => 'required|array',
            'actions.*.key' => 'required|string',
            'actions.*.label' => 'required|string',
            'actions.deep_link' => 'sometimes|string',
            'type' => 'sometimes|string|max:50',
            'category' => ['sometimes', Rule::enum(NotificationCategory::class)],
            'priority' => ['sometimes', Rule::enum(NotificationPriority::class)],
            'data' => 'sometimes|array',
        ]);

        $pipeline = $this->notificationService->sendActionable(
            recipients: $data['recipients'],
            title: $data['title'],
            body: $data['body'],
            actions: $data['actions'],
            type: $data['type'] ?? 'actionable',
            options: [
                'category' => isset($data['category']) ? NotificationCategory::from($data['category']) : NotificationCategory::SYSTEM,
                'priority' => isset($data['priority']) ? NotificationPriority::from($data['priority']) : NotificationPriority::NORMAL,
                'data' => $data['data'] ?? []
            ]
        );

        return $pipeline->toApiResponse();
    }

    /**
     * Schedule notification
     */
    public function schedule(Request $request)
    {
        $data = $request->validate([
            'scheduled_at' => 'required|date|after:now',
            'recipients' => 'required|array',
            'recipients.*' => 'integer|exists:users,id',
            'title' => 'required|string|max:255',
            'body' => 'required|string|max:1000',
            'type' => 'sometimes|string|max:50',
            'category' => ['sometimes', Rule::enum(NotificationCategory::class)],
            'priority' => ['sometimes', Rule::enum(NotificationPriority::class)],
            'data' => 'sometimes|array',
        ]);

        $pipeline = $this->notificationService->schedule(
            scheduledAt: Carbon::parse($data['scheduled_at']),
            recipients: $data['recipients'],
            title: $data['title'],
            body: $data['body'],
            type: $data['type'] ?? 'scheduled',
            options: [
                'category' => isset($data['category']) ? NotificationCategory::from($data['category']) : NotificationCategory::SYSTEM,
                'priority' => isset($data['priority']) ? NotificationPriority::from($data['priority']) : NotificationPriority::NORMAL,
                'data' => $data['data'] ?? []
            ]
        );

        return $pipeline->toApiResponse();
    }

    /**
     * Get notification templates
     */
    public function templates()
    {
        $templates = collect(NotificationTemplate::cases())->map(function ($template) {
            $data = $template->getTemplate();
            return [
                'key' => $template->value,
                'name' => $template->name,
                'title' => $data['title'],
                'body' => $data['body'],
                'category' => $data['category']->value,
                'priority' => $data['priority']->value,
                'is_actionable' => $data['is_actionable'] ?? false,
                'parameters' => $this->extractParameters($data['title'] . ' ' . $data['body']),
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $templates
        ]);
    }

    /**
     * Get notification categories
     */
    public function categories()
    {
        $categories = collect(NotificationCategory::cases())->map(function ($category) {
            return [
                'key' => $category->value,
                'name' => $category->name,
                'display_name' => $category->getDisplayName(),
                'icon' => $category->getIcon(),
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $categories
        ]);
    }

    /**
     * Get notification priorities
     */
    public function priorities()
    {
        $priorities = collect(NotificationPriority::cases())->map(function ($priority) {
            return [
                'key' => $priority->value,
                'name' => $priority->name,
                'color' => $priority->getColor(),
                'icon' => $priority->getIcon(),
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $priorities
        ]);
    }

    /**
     * Update FCM token
     */
    public function updateFcmToken(Request $request)
    {
        $data = $request->validate([
            'token' => 'required|string'
        ]);

        auth()->user()->update(['fcm_token' => $data['token']]);

        return response()->json([
            'status' => 'success',
            'message' => 'FCM token updated successfully'
        ]);
    }

    /**
     * Process scheduled notifications (admin only)
     */
    public function processScheduled()
    {
        $pipeline = $this->notificationService->processScheduled();
        return $pipeline->toApiResponse();
    }

    /**
     * Clean up expired notifications (admin only)
     */
    public function cleanupExpired()
    {
        $pipeline = $this->notificationService->cleanupExpired();
        return $pipeline->toApiResponse();
    }

    /**
     * Extract parameters from template text
     */
    private function extractParameters(string $text): array
    {
        preg_match_all('/\{([^}]+)\}/', $text, $matches);
        return array_unique($matches[1]);
    }
}
