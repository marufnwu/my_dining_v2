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


    public function __construct(protected ?Mess $mess = null) {}

    public function setMess(Mess $mess) : self {
        $this->mess = $mess;
        return $this;
    }

    static function isUserInSameMess(MessUser $messUser, ?Mess $mess = null): bool
    {
        $mess = $mess ?? app()->getMess();
        return  $mess?->id == $messUser->user->activeMess->id;
    }
    static function isUserInitiated(MessUser $messUser, ?Month $month = null): bool
    {
        $month = $month ?? app()->getMonth();
        return  $month->initiatedUser()->where("mess_user_id", $messUser->id)->exists();
    }

    // Add your service methods here

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
        $pipeline = $this->addUser( $user, $mess->memberRole()->first());


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
        return Pipeline::success(data: $mess->messUsers()->byStatus(MessUserStatus::Active)->get());
    }

    public function initiated(Month $month, $status): Pipeline
    {
        $messUser = $status ?  $month->initiatedUser()->with("messUser.user")->get()->pluck("messUser") : $month->notInitiatedUser;
        return Pipeline::success($messUser ?? collect());
    }

    public function initiateUser(MessUser $user): Pipeline
    {
        if (!MessUserService::isUserInSameMess($user)) {
            return Pipeline::error(message: "User is not in the same mess");
        }

        $month = app()->getMonth();

        if (MessUserService::isUserInitiated($user, $month)) {
            return Pipeline::error(message: "User is already initiated");
        }

        $month->initiatedUser()->create(['mess_user_id' => $user->id, "month_id" => $month->id, "mess_id" => app()->getMess()->id]);

        return Pipeline::success();
    }

    public function initiateAll(MessUser $user): Pipeline
    {
        if (!MessUserService::isUserInSameMess($user)) {
            return Pipeline::error(message: "User is not in the same mess");
        }

        $month = app()->getMonth();

        if (MessUserService::isUserInitiated($user, $month)) {
            return Pipeline::error(message: "User is already initiated");
        }

        $month->initiatedUser()->create(['mess_user_id' => $user->id, "month_id" => $month->id, "mess_id" => app()->getMess()->id]);

        return Pipeline::success();
    }

    public function getMessUser(User $user) : Pipeline {
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
        MessRequest::where('old_user_id', $user->id)
            ->where('status', MessJoinRequestStatus::PENDING)
            ->update(['status' => MessJoinRequestStatus::CANCELLED]);

        // Mark user as left
        $messUser->update([
            'left_at' => Carbon::now(),
            'status' => MessUserStatus::LEFT
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

        // Check if user has any pending requests
        $existingRequest = MessRequest::where('old_user_id', $user->id)
            ->where('status', MessJoinRequestStatus::PENDING)
            ->first();

        if ($existingRequest) {
            return Pipeline::error(message: "You have a pending join request. Please cancel it first.");
        }

        // Check if target mess is active
        if ($targetMess->status !== MessStatus::ACTIVE) {
            return Pipeline::error(message: "Cannot join inactive mess");
        }

        // Create join request
        $request = MessRequest::create([
            'user_name' => $user->name,
            'old_user_id' => $user->id,
            'old_mess_id' => $user->messUser?->mess_id,
            'new_mess_id' => $targetMess->id,
            'request_date' => Carbon::now(),
            'status' => MessJoinRequestStatus::PENDING
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
                    'join_request_exists' => \App\Models\MessRequest::where('old_user_id', \Illuminate\Support\Facades\Auth::id())
                        ->where('new_mess_id', $mess->id)
                        ->where('status', \App\Enums\MessJoinRequestStatus::PENDING)
                        ->exists(),
                ];
            });

        return Pipeline::success($messes);
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

        $requests = MessRequest::where('old_user_id', $user->id)
            ->with(['newMess', 'acceptedBy'])
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

        if ($request->old_user_id !== $user->id) {
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
        $mess = $mess ?? $this->mess ?? app()->getMess();

        if (!$mess) {
            return Pipeline::error(message: "No mess context available");
        }

        $requests = MessRequest::where('new_mess_id', $mess->id)
            ->where('status', MessJoinRequestStatus::PENDING)
            ->with(['user', 'oldMess'])
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

        if ($request->status !== MessJoinRequestStatus::PENDING) {
            return Pipeline::error(message: "Request is not pending");
        }        $request->load(['newMess', 'user']);
        /** @var Mess $targetMess */
        $targetMess = $request->newMess;
        if (!$targetMess || $targetMess->status !== MessStatus::ACTIVE) {
            return Pipeline::error(message: "Target mess is not active");
        }

        DB::beginTransaction();
        try {
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
                'new_user_id' => $requestingUser->id
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

        if ($request->status !== MessJoinRequestStatus::PENDING) {
            return Pipeline::error(message: "Request is not pending");
        }

        $request->update([
            'status' => MessJoinRequestStatus::REJECTED,
            'accept_date' => Carbon::now(),
            'accept_by' => $user->id
        ]);

        return Pipeline::success(message: "Join request rejected successfully");
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

            DB::commit();
            return Pipeline::success(message: "Mess closed successfully");

        } catch (\Exception $e) {
            DB::rollBack();
            return Pipeline::error(message: "Failed to close mess: " . $e->getMessage());
        }
    }
}
