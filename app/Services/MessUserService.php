<?php

namespace App\Services;

use App\DTOs\UserDto;
use App\Enums\AccountStatus;
use App\Enums\MessStatus;
use App\Enums\MessUserStatus;
use App\Exceptions\MustNotMessJoinException;
use App\Helpers\Pipeline;
use App\Models\Mess;
use App\Models\MessRole;
use App\Models\MessUser;
use App\Models\Month;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class MessUserService
{


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

    function addUser(Mess $mess, User $user, ?MessRole $role = null): Pipeline
    {

        if ($mess->status != MessStatus::ACTIVE) {
            return Pipeline::error(message: "Mess is not active");
        }


        if ($user->status != AccountStatus::ACTIVE->value) {
            return Pipeline::error(message: "User account is not active");
        }


        if ($user->activeMess) {
            throw new MustNotMessJoinException();
        }

        $messUser = $mess->messUsers()->create([
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
        $pipeline = $this->addUser($mess, $user, $mess->memberRole);


        if ($pipeline->isSuccess()) {
            DB::commit();
            $pipeline->withData($pipeline->data->load("user"));
        } else {
            DB::rollBack();
        }

        return $pipeline;
    }

    public function messMembers(): Pipeline
    {
        $mess = MessService::currentMess();
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
}
