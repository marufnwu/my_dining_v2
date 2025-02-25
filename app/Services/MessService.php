<?php

namespace App\Services;

use App\DTOs\UserDto;
use App\Enums\AccountStatus;
use App\Enums\ErrorCode;
use App\Enums\MessStatus;
use App\Enums\MessUserRole;
use App\Enums\MessUserStatus;
use App\Exceptions\MustNotMessJoinException;
use App\Helpers\Pipeline;
use App\Models\Mess;
use App\Models\MessRole;
use App\Models\Month;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class MessService
{
    public static function currentMess(): ?Mess
    {
        return UserService::currentUser()->activeMess ?? null;
    }

    function create($messName): Pipeline
    {

        DB::beginTransaction();

        $mess = Mess::create([
            "name" => $messName,
            "status" => MessStatus::ACTIVE->value,
        ]);


        $prmsnService = new MessPermissionService($mess);
        $roles = $prmsnService->addMessDefaultRoleAndPermission();
        $mess->load("adminRole");
        if ($mess) {
            $pipeline = $messUser = $this->addUser($mess, UserService::currentUser(), $mess->adminRole);
            if ($messUser->isSuccess()) {
                DB::commit();
                return Pipeline::success(data: $mess);
            }
        } else {
            $pipeline = Pipeline::error()->withMessage("Failed to create mess");
        }
        DB::rollBack();
        return $pipeline;
    }

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

        return Pipeline::success([
            "messUser" => $messUser,
        ]);
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
            $pipeline->withData($user);
        } else {
            DB::rollBack();
        }

        return $pipeline;
    }


}
