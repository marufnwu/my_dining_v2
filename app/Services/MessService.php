<?php

namespace App\Services;

use App\DTOs\UserDto;
use App\Enums\AccountStatus;
use App\Enums\ErrorCode;
use App\Enums\MessStatus;
use App\Enums\MessUserRole;
use App\Enums\MessUserStatus;
use App\Helpers\Pipeline;
use App\Models\Mess;
use App\Models\MessRole;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;

class MessService
{
    function create($messName) : Pipeline {

        DB::beginTransaction();

        $mess = Mess::create([
            "name"=>$messName,
            "status"=>MessStatus::ACTIVE->value,
        ]);


        $prmsnService = new MessPermissionService($mess);
        $roles = $prmsnService->addMessDefaultRoleAndPermission();
        $mess->load("adminRole");
        if($mess) {
            $pipeline = $messUser = $this->addUser($mess, UserService::currentUser(), $mess->adminRole);
            if($messUser->isSuccess()) {
                DB::commit();
                return Pipeline::success(data:$mess);
            }
        }else{
            $pipeline = Pipeline::error()->withMessage("Failed to create mess");
        }
        DB::rollBack();
        return $pipeline;
    }

    function addUser(Mess $mess, User $user, MessRole $role) : Pipeline {

        if($mess->status != MessStatus::ACTIVE) {
            return Pipeline::error(message: "Mess is not active");
        }


        if($user->status != AccountStatus::ACTIVE->value) {
            return Pipeline::error(message: "User account is not active");
        }

        if($user->activeMess){
            return Pipeline::error(message: "User is already in a mess", errorCode:ErrorCode::USER_ALREADY_IN_MESS->value);
        }

        $messUser = $mess->messUsers()->create([
            "user_id"=>$user->id,
            "mess_role_id"=>$role->id,
            "joined_at"=>Carbon::now(),
            "status"=>MessUserStatus::Active->value,
        ]);

        return Pipeline::success([
            "messUser"=>$messUser,
        ]);

    }

    function createAndAddUser(UserDto $userDto) : Pipeline {
        $userService = new UserService();
        $pipeline = $userService->createUser($userDto);

        if(!$pipeline->isSuccess()) {
           return $pipeline;
        }

        $user = $pipeline->data;

        $mess = UserService::currentUser()?->mess;
        dd($mess);

    }
}
