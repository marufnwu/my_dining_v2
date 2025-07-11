<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function index(Request $request)
    {
        $filters = $request->validate([
            'type' => 'sometimes|string',
            'unread_only' => 'sometimes|boolean',
            'per_page' => 'sometimes|integer|min:1|max:50'
        ]);

        $pipeline = $this->notificationService->getUserNotifications(auth()->user(), $filters);
        return $pipeline->toApiResponse();
    }

    public function markAsRead(Notification $notification)
    {
        $pipeline = $this->notificationService->markAsRead($notification);
        return $pipeline->toApiResponse();
    }

    public function markAllAsRead()
    {
        $notifications = Notification::query()
            ->where('mess_id', app()->getMess()->id)
            ->forUser(auth()->id())
            ->unread()
            ->get();

        foreach ($notifications as $notification) {
            $this->notificationService->markAsRead($notification);
        }

        return response()->json([
            'error' => false,
            'message' => 'All notifications marked as read'
        ]);
    }

    public function updateFcmToken(Request $request)
    {
        $data = $request->validate([
            'token' => 'required|string'
        ]);

        auth()->user()->update(['fcm_token' => $data['token']]);

        return response()->json([
            'error' => false,
            'message' => 'FCM token updated successfully'
        ]);
    }
}
