<?php

namespace App\Services;

use App\Enums\AccountStatus;
use App\Enums\MessStatus;
use App\Enums\MessUserRole;
use App\Enums\MessUserStatus;
use App\Helpers\Pipeline;
use App\Models\Mess;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class MessService
{
    function create($messName) : Pipeline {

        DB::beginTransaction();

        $mess = Mess::create([
            "name"=>$messName,
            "status"=>MessStatus::ACTIVE->value,
        ]);

        if($mess) {
            $pipeline = $messUser = $this->addUser($mess, UserService::currentUser(), MessUserRole::Admin);
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

    function addUser(Mess $mess, User $user, MessUserRole $role) : Pipeline {

        if($mess->status != MessStatus::ACTIVE) {
            return Pipeline::error(message: "Mess is not active");
        }


        if($user->status != AccountStatus::ACTIVE->value) {
            return Pipeline::error(message: "User account is not active");
        }

        if($user->activeMess){
            return Pipeline::error(message: "User is already in a mess");
        }

        $messUser = $mess->messUsers()->create([
            "user_id"=>$user->id,
            "role"=>$role->value,
            "joined_at"=>Carbon::now(),
            "status"=>MessUserStatus::Active->value,
        ]);

        return Pipeline::success([
            "messUser"=>$messUser,
        ]);

    }

    function createAndAddUser(){
        $userService = new UserService();
       //$user = $userService->createUser("name", "country", "phone", "password", "email", "city", ");
    }
}
