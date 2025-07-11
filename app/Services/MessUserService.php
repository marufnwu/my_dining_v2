<?php

namespace App\Services;

use App\DTOs\UserDto;
use App\Enums\AccountStatus;
use App\Enums\MessJoinRequestStatus;
use App\Enums\MessStatus;
use App\Enums\MessUserStatus;
use App\Exceptions\MustNotMessJoinException;
use App\Helpers\Pipeline;
use App\Models\Mess;
use App\Models\MessRequest;
use App\Models\MessRole;
use App\Models\MessUser;
use App\Models\Month;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MessUserService
{
    protected ?Mess $mess;
    protected NotificationService $notificationService;

    public function __construct(?Mess $mess = null, ?NotificationService $notificationService = null)
    {
        $this->mess = $mess;
        $this->notificationService = $notificationService ?? app(NotificationService::class);
    }

    public function setMess(Mess $mess): self
    {
        $this->mess = $mess;
        return $this;
    }

    static function isUserInSameMess(MessUser $messUser, ?Mess $mess = null): bool
    {
        $mess = $mess ?? app()->getMess();
        return $mess?->id == $messUser->user->activeMess->id;
    }

    static function isUserInitiated(MessUser $messUser, ?Month $month = null): bool
    {
        $month = $month ?? app()->getMonth();
        return $month->initiatedUser()->where("mess_user_id", $messUser->id)->exists();
    }

    function addUser($user, ?MessRole $role = null): Pipeline
    {
        if ($this->mess->status != MessStatus::ACTIVE) {
            return Pipeline::error(message: "Mess is not active");
        }

        if ($user->status != AccountStatus::ACTIVE->value) {
            return Pipeline::error(message: "User account is not active");
        }

        if ($user->activeMess) {
            throw new MustNotMessJoinException();
        }

        $messUser = $this->mess->messUsers()->create([
            "user_id" => $user->id,
            "mess_role_id" => $role ? $role->id : null,
            "joined_at" => Carbon::now(),
            "status" => MessUserStatus::Active->value,
        ]);

        // Notify about new member
        $this->notificationService->sendNotification([
            'title' => 'New Member Joined',
            'body' => "{$user->name} has joined the mess",
            'type' => 'member_joined',
            'is_broadcast' => true,
            'extra_data' => [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'role' => $role?->role
            ]
        ]);

        return Pipeline::success($messUser);
    }

    function createAndAddUser(UserDto $userDto): Pipeline
    {
        DB::beginTransaction();
        $userService = new UserService();
        $pipeline = $userService->createUser($userDto);

        if (!$pipeline->isSuccess()) {
            DB::rollBack();
            return $pipeline;
        }

        $user = $pipeline->data;

        $mess = MessService::currentMess();
        $pipeline = $this->addUser($user, $mess->memberRole()->first());

        if ($pipeline->isSuccess()) {
            DB::commit();
            $pipeline->withData($pipeline->data->load("user"));
        } else {
            DB::rollBack();
        }

        return $pipeline;
    }

    public function messMembers(?Mess $mess = null): Pipeline
    {
        $mess = $mess ?? MessService::currentMess();

        // Get all mess users with their status
        $allMessUsers = $mess->messUsers()->with('user', 'role')->get();

        // Build array of {status, users: []}
        $groupedMembers = [];

        foreach (MessUserStatus::cases() as $status) {
            $users = $allMessUsers
                ->filter(function ($messUser) use ($status) {
                    return $messUser->status === $status->value;
                })
                ->values()
                ->toArray();
            if (!empty($users)) {
                $groupedMembers[] = [
                    'status' => $status->value,
                    'users' => $users
                ];
            }
        }

        return Pipeline::success(data: $groupedMembers);
    }

    public function initiated(Month $month, $status): Pipeline
    {
        if ($status) {
            $messUser = $month->initiatedUser()->with("messUser.user")->get()->pluck("messUser");
        } else {
            $messUser = $month->notInitiatedUser()->byStatus(MessUserStatus::Active)->get();
        }
        return Pipeline::success($messUser ?? collect());
    }

    public function initiateUser(MessUser $user): Pipeline
    {
        if (!MessUserService::isUserInSameMess($user)) {
            return Pipeline::error(message: "User is not in the same mess");
        }

        // Check if user status is active
        if ($user->status !== MessUserStatus::Active->value) {
            return Pipeline::error(message: "User status is not active");
        }

        $month = app()->getMonth();

        if (MessUserService::isUserInitiated($user, $month)) {
            return Pipeline::error(message: "User is already initiated");
        }

        $month->initiatedUser()->create(['mess_user_id' => $user->id, "month_id" => $month->id, "mess_id" => app()->getMess()->id]);

        return Pipeline::success();
    }

    public function initiateAll(): Pipeline
    {
        $month = app()->getMonth();
        $mess = app()->getMess();

        if (!$month || !$mess) {
            return Pipeline::error(message: "Month or mess context not available");
        }

        // Get all active mess users who are not already initiated
        $activeMessUsers = $mess->messUsers()
            ->byStatus(MessUserStatus::Active)
            ->whereDoesntHave('initiatedUser', function ($query) use ($month) {
                $query->where('month_id', $month->id);
            })
            ->get();

        if ($activeMessUsers->isEmpty()) {
            return Pipeline::success(message: "No users to initiate");
        }

        $initiatedCount = 0;
        $failedCount = 0;

        foreach ($activeMessUsers as $messUser) {
            try {
                $month->initiatedUser()->create([
                    'mess_user_id' => $messUser->id,
                    'month_id' => $month->id,
                    'mess_id' => $mess->id
                ]);
                $initiatedCount++;
            } catch (\Exception $e) {
                $failedCount++;
            }
        }

        return Pipeline::success([
            'initiated_count' => $initiatedCount,
            'failed_count' => $failedCount,
            'total_processed' => $activeMessUsers->count()
        ], message: "Bulk initiation completed. Initiated: {$initiatedCount}, Failed: {$failedCount}");
    }

    public function getMessUser(User $user): Pipeline
    {
        $messUser = MessUser::with("mess", "user", "role.permissions")->where("mess_id", $this->mess?->id)->where("user_id", $user->id)->first();

        return Pipeline::success($messUser);
    }

    /**
     * Get current user's mess information
     */
    public function getCurrentMessInfo(bool $withUser = false): Pipeline
    {
        $user = Auth::user();
        if (!$user) {
            return Pipeline::error(message: "User not authenticated");
        }

        $messUser = $user->messUser;
        if (!$messUser) {
            return Pipeline::error(message: "User is not part of any mess");
        }

        return Pipeline::success($messUser);
    }

    /**
     * Leave current mess
     */
    public function leaveMess(): Pipeline
    {
        $user = Auth::user();
        if (!$user) {
            return Pipeline::error(message: "User not authenticated");
        }

        $messUser = $user->messUser;
        if (!$messUser) {
            return Pipeline::error(message: "User is not part of any mess");
        }

        // Check if user is admin and if there are other admins
        if ($messUser->role?->is_admin) {
            $otherAdmins = $messUser->mess->messUsers()
                ->whereHas('role', function ($query) {
                    $query->where('is_admin', true);
                })
                ->where('id', '!=', $messUser->id)
                ->whereNull('left_at')
                ->count();

            if ($otherAdmins === 0) {
                return Pipeline::error(message: "Cannot leave mess. You are the only admin. Please assign another admin first.");
            }
        }

        // Cancel any pending join requests
        MessRequest::where('user_id', $user->id)
            ->where('status', MessJoinRequestStatus::PENDING)
            ->update(['status' => MessJoinRequestStatus::CANCELLED]);

        $messId = $messUser->mess_id;
        $userName = $user->name;

        // Mark user as left
        $messUser->update([
            'left_at' => Carbon::now(),
            'status' => MessUserStatus::LEFT
        ]);

        // Notify about member leaving
        $this->notificationService->sendNotification([
            'title' => 'Member Left',
            'body' => "{$userName} has left the mess",
            'type' => 'member_left',
            'is_broadcast' => true,
            'extra_data' => [
                'user_id' => $user->id,
                'user_name' => $userName
            ]
        ]);

        return Pipeline::success(message: "Successfully left the mess");
    }

    /**
     * Send join request to another mess
     */
    public function sendJoinRequest(Mess $targetMess): Pipeline
    {
        $user = Auth::user();
        if (!$user) {
            return Pipeline::error(message: "User not authenticated");
        }

        // Check if user is already in a mess
        if ($user->messUser) {
            return Pipeline::error(message: "You are already in a mess. Please leave it first before joining another.");
        }

        // Check if user has any pending requests
        $existingRequest = MessRequest::where('user_id', $user->id)
            ->where('status', MessJoinRequestStatus::PENDING)
            ->first();

        if ($existingRequest) {
            return Pipeline::error(message: "You have a pending join request. Please cancel it first.");
        }

        // Check if target mess is active
        if ($targetMess->status !== MessStatus::ACTIVE) {
            return Pipeline::error(message: "Cannot join deactivated mess");
        }

        // Create join request (user might not be in any mess currently)
        $request = MessRequest::create([
            'user_name' => $user->name,
            'user_id' => $user->id,
            'old_mess_user_id' => $user->messUser?->id, // nullable - user might not be in any mess
            'old_mess_id' => $user->messUser?->mess_id, // nullable - user might not be in any mess
            'new_mess_id' => $targetMess->id,
            'request_date' => Carbon::now(),
            'status' => MessJoinRequestStatus::PENDING
        ]);

        // Notify mess admins about join request
        $this->notificationService->sendNotification([
            'title' => 'New Join Request',
            'body' => "{$user->name} has requested to join the mess",
            'type' => 'join_request',
            'is_broadcast' => true,
            'extra_data' => [
                'request_id' => $request->id,
                'user_id' => $user->id,
                'user_name' => $user->name
            ]
        ]);

        return Pipeline::success($request, message: "Join request sent successfully");
    }

    /**
     * Get list of available messes to join
     */
    public function getAvailableMesses(): Pipeline
    {
        $currentMessId = Auth::user()->messUser?->mess_id;

        $messes = Mess::where('status', MessStatus::ACTIVE)
            ->when($currentMessId, function ($query, $currentMessId) {
                return $query->where('id', '!=', $currentMessId);
            })
            ->with(['messUsers' => function ($query) {
                $query->whereNull('left_at');
            }])
            ->get()
            ->map(function ($mess) {
                return [
                    'mess' => $mess->toArray(),
                    'member_count' => $mess->messUsers->count(),
                    'is_accepting_members' => $mess->is_accepting_members,
                    'join_request_exists' => \App\Models\MessRequest::where('user_id', \Illuminate\Support\Facades\Auth::id())
                        ->where('new_mess_id', $mess->id)
                        ->where('status', \App\Enums\MessJoinRequestStatus::PENDING)
                        ->exists(),
                ];
            });

        return Pipeline::success($messes);
    }

    /**
     * Send join request when user is not in any mess
     */
    public function sendJoinRequestWithoutMess(Mess $targetMess): Pipeline
    {
        $user = Auth::user();
        if (!$user) {
            return Pipeline::error(message: "User not authenticated");
        }

        // Check if user is already in a mess
        if ($user->messUser) {
            return Pipeline::error(message: "You are already in a mess. Please leave it first.");
        }

        // Check if user has any pending requests
        $existingRequest = MessRequest::where('user_id', $user->id)
            ->where('status', MessJoinRequestStatus::PENDING)
            ->first();

        if ($existingRequest) {
            return Pipeline::error(message: "You have a pending join request. Please cancel it first.");
        }

        // Check if target mess is active
        if ($targetMess->status !== MessStatus::ACTIVE) {
            return Pipeline::error(message: "Cannot join deactivated mess");
        }

        // Create join request (user is not in any mess)
        $request = MessRequest::create([
            'user_name' => $user->name,
            'user_id' => $user->id,
            'old_mess_user_id' => null, // user is not in any mess
            'old_mess_id' => null, // user is not in any mess
            'new_mess_id' => $targetMess->id,
            'request_date' => Carbon::now(),
            'status' => MessJoinRequestStatus::PENDING
        ]);

        // Notify mess admins about join request
        $this->notificationService->sendNotification([
            'title' => 'New Join Request',
            'body' => "{$user->name} has requested to join the mess",
            'type' => 'join_request',
            'is_broadcast' => true,
            'extra_data' => [
                'request_id' => $request->id,
                'user_id' => $user->id,
                'user_name' => $user->name
            ]
        ]);

        return Pipeline::success($request, message: "Join request sent successfully");
    }

    /**
     * Get user's join request history
     */
    public function getUserJoinRequests(): Pipeline
    {
        $user = Auth::user();
        if (!$user) {
            return Pipeline::error(message: "User not authenticated");
        }

        $requests = MessRequest::where('user_id', $user->id)
            ->with(['newMess', 'oldMess', 'oldMessUser', 'newMessUser', 'acceptedBy'])
            ->orderBy('created_at', 'desc')
            ->get();

        return Pipeline::success($requests);
    }

    /**
     * Cancel pending join request
     */
    public function cancelJoinRequest(MessRequest $request): Pipeline
    {
        $user = Auth::user();
        if (!$user) {
            return Pipeline::error(message: "User not authenticated");
        }

        if ($request->user_id !== $user->id) {
            return Pipeline::error(message: "You can only cancel your own requests");
        }

        if ($request->status !== MessJoinRequestStatus::PENDING) {
            return Pipeline::error(message: "Can only cancel pending requests");
        }

        $request->update(['status' => MessJoinRequestStatus::CANCELLED]);

        return Pipeline::success(message: "Join request cancelled successfully");
    }

    /**
     * Get pending join requests for current mess (requires permission)
     */
    public function getMessJoinRequests(?Mess $mess = null): Pipeline
    {
        $user = Auth::user();
        if (!$user) {
            return Pipeline::error(message: "User not authenticated");
        }

        $mess = $mess ?? $this->mess ?? app()->getMess();

        if (!$mess) {
            return Pipeline::error(message: "No mess context available");
        }

        // Check if user has permission to view join requests
        $messUser = $user->messUser;
        if (!$messUser || !$messUser->role?->is_admin) {
            return Pipeline::error(message: "You don't have permission to view join requests");
        }

        $requests = MessRequest::where('new_mess_id', $mess->id)
            ->where('status', MessJoinRequestStatus::PENDING)
            ->with(['user', 'oldMess', 'oldMessUser'])
            ->orderBy('created_at', 'asc')
            ->get();

        return Pipeline::success($requests);
    }

    /**
     * Accept join request (requires permission)
     */
    public function acceptJoinRequest(MessRequest $request): Pipeline
    {
        $user = Auth::user();
        if (!$user) {
            return Pipeline::error(message: "User not authenticated");
        }

        // Check if user has permission to accept join requests
        $currentMess = app()->getMess();
        if (!$currentMess) {
            return Pipeline::error(message: "No mess context available");
        }

        // Check if the request is for the current mess
        if ($request->new_mess_id !== $currentMess->id) {
            return Pipeline::error(message: "Request is not for this mess");
        }

        // Check if user has admin role or permission to accept join requests
        $messUser = $user->messUser;
        if (!$messUser || !$messUser->role?->is_admin) {
            return Pipeline::error(message: "You don't have permission to accept join requests");
        }

        if ($request->status !== MessJoinRequestStatus::PENDING) {
            return Pipeline::error(message: "Request is not pending");
        }

        DB::beginTransaction();
        try {
            $request->load(['newMess', 'user']);
            /** @var Mess $targetMess */
            $targetMess = $request->newMess;
            if (!$targetMess || $targetMess->status !== MessStatus::ACTIVE) {
                return Pipeline::error(message: "Target mess is not active");
            }

            // If user is currently in a mess, leave it
            /** @var User $requestingUser */
            $requestingUser = $request->user;
            if ($requestingUser->messUser && !$requestingUser->messUser->left_at) {
                $requestingUser->messUser->update([
                    'left_at' => Carbon::now(),
                    'status' => MessUserStatus::LEFT
                ]);
            }

            // Add user to new mess
            $messUserService = new MessUserService($targetMess);
            $addResult = $messUserService->addUser($requestingUser, $targetMess->memberRole()->first());

            if (!$addResult->isSuccess()) {
                DB::rollBack();
                return $addResult;
            }

            // Update request status
            $request->update([
                'status' => MessJoinRequestStatus::APPROVED,
                'accept_date' => Carbon::now(),
                'accept_by' => $user->id,
                'new_mess_user_id' => $addResult->data->id
            ]);

            // Notify user about accepted request
            $this->notificationService->sendNotification([
                'user_id' => $requestingUser->id,
                'title' => 'Join Request Accepted',
                'body' => "Your request to join {$targetMess->name} has been accepted",
                'type' => 'join_request_accepted',
                'extra_data' => [
                    'mess_id' => $targetMess->id,
                    'mess_name' => $targetMess->name
                ]
            ]);

            DB::commit();
            return Pipeline::success($addResult->data, message: "Join request accepted successfully");

        } catch (\Exception $e) {
            DB::rollBack();
            return Pipeline::error(message: "Failed to accept join request: " . $e->getMessage());
        }
    }

    /**
     * Reject join request (requires permission)
     */
    public function rejectJoinRequest(MessRequest $request, ?string $reason = null): Pipeline
    {
        $user = Auth::user();
        if (!$user) {
            return Pipeline::error(message: "User not authenticated");
        }

        // Check if user has permission to reject join requests
        $currentMess = app()->getMess();
        if (!$currentMess) {
            return Pipeline::error(message: "No mess context available");
        }

        // Check if the request is for the current mess
        if ($request->new_mess_id !== $currentMess->id) {
            return Pipeline::error(message: "Request is not for this mess");
        }

        // Check if user has admin role or permission to reject join requests
        $messUser = $user->messUser;
        if (!$messUser || !$messUser->role?->is_admin) {
            return Pipeline::error(message: "You don't have permission to reject join requests");
        }

        if ($request->status !== MessJoinRequestStatus::PENDING) {
            return Pipeline::error(message: "Request is not pending");
        }

        $request->update([
            'status' => MessJoinRequestStatus::REJECTED,
            'reject_reason' => $reason,
            'reject_by' => $user->id,
            'reject_date' => Carbon::now()
        ]);

        // Notify user about rejected request
        $this->notificationService->sendNotification([
            'user_id' => $request->user_id,
            'title' => 'Join Request Rejected',
            'body' => "Your request to join has been rejected" . ($reason ? ": $reason" : ""),
            'type' => 'join_request_rejected',
            'extra_data' => [
                'reason' => $reason,
                'mess_id' => $currentMess->id,
                'mess_name' => $currentMess->name
            ]
        ]);

        return Pipeline::success(message: "Join request rejected successfully");
    }

    /**
     * Bulk accept join requests (requires admin permission)
     */
    public function bulkAcceptJoinRequests(array $requestIds): Pipeline
    {
        $user = Auth::user();
        if (!$user) {
            return Pipeline::error(message: "User not authenticated");
        }

        // Check if user has permission
        $currentMess = app()->getMess();
        if (!$currentMess) {
            return Pipeline::error(message: "No mess context available");
        }

        $messUser = $user->messUser;
        if (!$messUser || !$messUser->role?->is_admin) {
            return Pipeline::error(message: "You don't have permission to accept join requests");
        }

        $requests = MessRequest::whereIn('id', $requestIds)
            ->where('new_mess_id', $currentMess->id)
            ->where('status', MessJoinRequestStatus::PENDING)
            ->with(['newMess', 'user'])
            ->get();

        if ($requests->isEmpty()) {
            return Pipeline::error(message: "No valid pending requests found");
        }

        DB::beginTransaction();
        try {
            $acceptedCount = 0;
            $failedCount = 0;

            foreach ($requests as $request) {
                $result = $this->acceptJoinRequest($request);
                if ($result->isSuccess()) {
                    $acceptedCount++;
                } else {
                    $failedCount++;
                }
            }

            DB::commit();
            return Pipeline::success([
                'accepted' => $acceptedCount,
                'failed' => $failedCount
            ], message: "Bulk operation completed. Accepted: {$acceptedCount}, Failed: {$failedCount}");

        } catch (\Exception $e) {
            DB::rollBack();
            return Pipeline::error(message: "Failed to process bulk requests: " . $e->getMessage());
        }
    }

    /**
     * Get join request statistics for current mess (requires admin permission)
     */
    public function getJoinRequestStats(?Mess $mess = null): Pipeline
    {
        $user = Auth::user();
        if (!$user) {
            return Pipeline::error(message: "User not authenticated");
        }

        $mess = $mess ?? $this->mess ?? app()->getMess();

        if (!$mess) {
            return Pipeline::error(message: "No mess context available");
        }

        // Check if user has permission to view statistics
        $messUser = $user->messUser;
        if (!$messUser || !$messUser->role?->is_admin) {
            return Pipeline::error(message: "You don't have permission to view join request statistics");
        }

        $stats = [
            'pending' => MessRequest::where('new_mess_id', $mess->id)
                ->where('status', MessJoinRequestStatus::PENDING)
                ->count(),
            'approved' => MessRequest::where('new_mess_id', $mess->id)
                ->where('status', MessJoinRequestStatus::APPROVED)
                ->count(),
            'rejected' => MessRequest::where('new_mess_id', $mess->id)
                ->where('status', MessJoinRequestStatus::REJECTED)
                ->count(),
            'cancelled' => MessRequest::where('new_mess_id', $mess->id)
                ->where('status', MessJoinRequestStatus::CANCELLED)
                ->count(),
            'total' => MessRequest::where('new_mess_id', $mess->id)->count(),
        ];

        return Pipeline::success($stats);
    }

    /**
     * Bulk reject join requests (requires admin permission)
     */
    public function bulkRejectJoinRequests(array $requestIds, ?string $reason = null): Pipeline
    {
        $user = Auth::user();
        if (!$user) {
            return Pipeline::error(message: "User not authenticated");
        }

        // Check if user has permission
        $currentMess = app()->getMess();
        if (!$currentMess) {
            return Pipeline::error(message: "No mess context available");
        }

        $messUser = $user->messUser;
        if (!$messUser || !$messUser->role?->is_admin) {
            return Pipeline::error(message: "You don't have permission to reject join requests");
        }

        $requests = MessRequest::whereIn('id', $requestIds)
            ->where('new_mess_id', $currentMess->id)
            ->where('status', MessJoinRequestStatus::PENDING)
            ->get();

        if ($requests->isEmpty()) {
            return Pipeline::error(message: "No valid pending requests found");
        }

        DB::beginTransaction();
        try {
            $rejectedCount = 0;

            foreach ($requests as $request) {
                $request->update([
                    'status' => MessJoinRequestStatus::REJECTED,
                    'accept_date' => Carbon::now(),
                    'accept_by' => $user->id
                ]);
                $rejectedCount++;
            }

            DB::commit();
            return Pipeline::success([
                'rejected' => $rejectedCount
            ], message: "Bulk reject completed. Rejected: {$rejectedCount} requests");

        } catch (\Exception $e) {
            DB::rollBack();
            return Pipeline::error(message: "Failed to process bulk reject: " . $e->getMessage());
        }
    }

    /**
     * Close mess (requires admin permission)
     */
    public function closeMess(?Mess $mess = null): Pipeline
    {
        $mess = $mess ?? $this->mess ?? app()->getMess();

        if (!$mess) {
            return Pipeline::error(message: "No mess context available");
        }

        // Only allow closing if user is admin
        $user = Auth::user();
        if (!$user || !$user->role?->is_admin) {
            return Pipeline::error(message: "Only admins can close a mess");
        }

        DB::beginTransaction();
        try {
            // Mark all active mess users as left
            $mess->messUsers()
                ->whereNull('left_at')
                ->update([
                    'left_at' => Carbon::now(),
                    'status' => MessUserStatus::LEFT
                ]);

            // Reject all pending join requests
            MessRequest::where('new_mess_id', $mess->id)
                ->where('status', MessJoinRequestStatus::PENDING)
                ->update([
                    'status' => MessJoinRequestStatus::REJECTED,
                    'accept_date' => Carbon::now(),
                    'accept_by' => $user->id
                ]);

            // Close the mess
            $mess->update(['status' => MessStatus::DEACTIVATED]);

            // Notify about mess closure
            $this->notificationService->sendNotification([
                'title' => 'Mess Closed',
                'body' => "The mess {$mess->name} has been closed",
                'type' => 'mess_closed',
                'is_broadcast' => true,
                'extra_data' => [
                    'mess_id' => $mess->id,
                    'mess_name' => $mess->name
                ]
            ]);

            DB::commit();
            return Pipeline::success(message: "Mess closed successfully");

        } catch (\Exception $e) {
            DB::rollBack();
            return Pipeline::error(message: "Failed to close mess: " . $e->getMessage());
        }
    }
}
