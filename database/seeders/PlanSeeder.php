<?php

namespace Database\Seeders;

use App\Enums\Feature;
use App\Enums\SubPlan;
use App\Models\Plan;
use App\Models\PlanFeature;
use App\Models\PlanPackage;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    private $data = [
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
            Feature::MEMBER_LIMIT->value => [
                "is_countable" => true,
            ],
            Feature::MESS_REPORT_GENERATE->value => [
                "is_countable" => true,
            ],
            Feature::MEAL_ADD_NOTIFICATION->value => [
                "is_countable" => true,
            ],
            Feature::BALANCE_ADD_NOTIFICATION->value => [
                "is_countable" => true,
            ],
            Feature::PURCHASE_NOTIFICATION->value => [
                "is_countable" => false,
            ],
        ],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Plan::truncate();
        PlanPackage::truncate();
        PlanFeature::truncate();

        $plans = $this->data['plans'];
        $featureData = $this->data['features'];


        foreach ($plans as $planData) {
            $plan = Plan::create([
                'name' => $planData['name'],
                'keyword' => $planData['keyword'],
                'is_free' => $planData['is_free'],
                'is_active' => $planData['is_active'],
            ]);

            foreach ($featureData as $feature => $data) {
                    $isCountable = $data['is_countable'];
                    $usageLimit = 100;

                    $plan->features()->create([
                        'name' => $feature,
                        'is_countable' => $isCountable,
                        'usage_limit' => $isCountable ? $usageLimit : null,
                    ]);

            }

            foreach ($planData['packages'] as $package) {
                $plan->packages()->create([
                    'is_trial' => $package['is_trial'],
                    'is_free' => $planData['is_free'],
                    'duration' => $package['duration'],
                    'price' => $package['price'],
                    'is_active' => $planData['is_active'],
                ]);
            }
        }
    }
}
