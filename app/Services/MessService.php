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
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class MessService
{
    public static function currentMess(): ?Mess
    {
        return UserService::currentUser()->activeMess ?? null;
    }

    function messRoles():Collection {
        return self::currentMess()?->roles ?? collect();
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

            $mus = new MessUserService();

            $pipeline = $messUser = $mus->addUser($mess, UserService::currentUser(), $mess->adminRole);
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


}
