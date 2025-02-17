<?php

use App\Constants\MessPermission;
use App\Constants\MessUserRole;

return [
    'default_roles' => [
        [
            'role' => MessUserRole::MANAGER,
            'permissions' => [
                MessPermission::MEAL_MANAGEMENT,
                MessPermission::REPORT_GENERATION,
                MessPermission::USER_MANAGEMENT,
                'manage_mess',
            ],
        ],
        [
            'role' => MessUserRole::ADMIN,
            'permissions' => [
                'view_mess',
            ],
        ],
    ],
];
