<?php

use App\Enums\Feature;
use App\Enums\SubPlan;

return [
    'plans' => [
        [
            'name' => 'Basic',
            'keyword' => SubPlan::BASIC->value,
            'is_free' => true,
            'is_active' => true,
            'packages' => [
                [
                    'duration' => 3, // 1 month
                    'price' => 0,
                    'is_trial' => true,
                ],
                [
                    'duration' => 180, // 6 months
                    'price' => 49.99,
                    'is_trial' => false,
                ],
                [
                    'duration' => 365, // 12 months
                    'price' => 99.99,
                    'is_trial' => false,
                ],
            ],
        ],
        [
            'name' => 'Premium',
            'keyword' => SubPlan::PREMIUM->value,
            'is_free' => false,
            'is_active' => true,
            'packages' => [
                [
                    'duration' => 3, // 1 month
                    'price' => 9.99,
                    'is_trial' => true,
                ],
                [
                    'duration' => 180, // 6 months
                    'price' => 49.99,
                    'is_trial' => false,
                ],
                [
                    'duration' => 365, // 12 months
                    'price' => 99.99,
                    'is_trial' => false,
                ],
            ],
        ],
        [
            'name' => 'Enterprise',
            'keyword' => SubPlan::ENTERPRISE->value,
            'is_free' => false,
            'is_active' => true,
            'packages' => [
                [
                    'duration' => 3, // 1 month
                    'price' => 19.99,
                    'is_trial' => true,
                ],
                [
                    'duration' => 180, // 6 months
                    'price' => 99.99,
                    'is_trial' => false,
                ],
                [
                    'duration' => 365, // 12 months
                    'price' => 199.99,
                    'is_trial' => false,
                ],
            ],
        ],
    ],
    'features' => [
        Feature::MEMBER_LIMIT => [
            "is_countable" => true,
            SubPlan::BASIC => [
                "usage_limit" => 10,
            ],
            SubPlan::PREMIUM => [
                "usage_limit" => 20,
            ],
            SubPlan::ENTERPRISE => [
                "usage_limit" => 50,
            ],
        ],
        Feature::MESS_REPORT_GENERATE => [
            "is_countable" => true,
            SubPlan::PREMIUM => [
                "usage_limit" => 10,
            ],
            SubPlan::ENTERPRISE => [
                "usage_limit" => 20,
            ],
        ],
        Feature::MEAL_ADD_NOTIFICATION => [
            "is_countable" => true,
            SubPlan::BASIC => [
                "usage_limit" => 100,
            ],
            SubPlan::PREMIUM => [
                "usage_limit" => 200,
            ],
            SubPlan::ENTERPRISE => [
                "usage_limit" => 500,
            ],
        ],
        Feature::BALANCE_ADD_NOTIFICATION => [
            "is_countable" => true,
            SubPlan::BASIC => [
                "usage_limit" => 50,
            ],
            SubPlan::PREMIUM => [
                "usage_limit" => 100,
            ],
            SubPlan::ENTERPRISE => [
                "usage_limit" => 200,
            ],
        ],
        Feature::PURCHASE_NOTIFICATION => [
            "is_countable" => false,
            SubPlan::PREMIUM => [
                "usage_limit" => null,
            ],
            SubPlan::ENTERPRISE => [
                "usage_limit" => null,
            ],
        ],
    ],
];
