<?php

namespace App\Services;

use App\Enums\MessPermission;
use App\Enums\MessUserRole;
use App\Models\Mess;

class MessPermissionService
{

    private const ROLE = [

        MessUserRole::Admin->value => [
            MessPermission::ALL->value
        ],
        
        MessUserRole::Manager->value => [
            MessPermission::START_NEW_MONTH->value,
            MessPermission::MEAL_MANAGEMENT->value,
            MessPermission::MEAL_MANAGEMENT->value,
            MessPermission::FUND_MANAGEMENT->value,
            MessPermission::MESS_SETTING->value,
            MessPermission::MESS_REPORT->value,
            MessPermission::EXPENSE_MANAGEMENT->value,
            MessPermission::MESS_NOTICE->value,
            MessPermission::CHNAGE_MANAGER->value,
            MessPermission::MANAGE_DEPOSIT->value,

        ],
        MessUserRole::Admin->value => [
            MessPermission::ALL->value
        ],
    ];



    function addMessDefaultRoleAndPermission(Mess $mess){
        $mess->roles()->create([
            'role' => MessUserRole::Admin->value,
            'permissions' => self::DDEFAYLT_ADMIN_PERMISSION
        ]);
    }
}
