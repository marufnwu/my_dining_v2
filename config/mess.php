<?php

use App\Constants\MessPermission;
use App\Constants\MessUserRole;

return [
    'default_roles' => [
        [
            'role' => MessUserRole::MANAGER,
            'permissions' => [
                MessPermission::MEAL_MANAGEMENT,
                MessPermission::MEAL_REQUEST_MANAGEMENT,
                MessPermission::REPORT_MANAGEMENT,
                MessPermission::USER_MANAGEMENT,
                MessPermission::PURCHASE_MANAGEMENT,
                MessPermission::DEPOSIT_MANAGEMENT,
                MessPermission::NOTICE_MANAGEMENT,
            ],
        ],
        [
            'role' => MessUserRole::ADMIN,
            "is_admin"=>true,
        ],
    ],
];
