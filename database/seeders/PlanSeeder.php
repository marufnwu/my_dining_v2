<?php

namespace Database\Seeders;

use App\Constants\Feature;
use App\Constants\SubPlan;
use App\Models\Plan;
use App\Models\PlanFeature;
use App\Models\PlanPackage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PlanSeeder extends Seeder
{
    private const DURATION_1_MONTH = 3;
    private const DURATION_6_MONTHS = 180;
    private const DURATION_12_MONTHS = 365;

    private $data = [
        'plans' => [
            [
                'name' => 'Basic',
                'keyword' => SubPlan::BASIC,
                'is_free' => true,
                'is_active' => true,
                'features' => [
                    Feature::MEMBER_LIMIT => ['is_countable' => true, 'usage_limit' => 10],
                    Feature::MESS_REPORT_GENERATE => ['is_countable' => true, 'usage_limit' => 5],
                    Feature::MEAL_ADD_NOTIFICATION => ['is_countable' => false],
                ],
                'packages' => [
                    [
                        'duration' => self::DURATION_1_MONTH,
                        'price' => 0,
                        'is_trial' => true,
                    ],
                    [
                        'duration' => self::DURATION_6_MONTHS,
                        'price' => 49.99,
                        'is_trial' => false,
                    ],
                    [
                        'duration' => self::DURATION_12_MONTHS,
                        'price' => 99.99,
                        'is_trial' => false,
                    ],
                ],
            ],
            [
                'name' => 'Premium',
                'keyword' => SubPlan::PREMIUM,
                'is_free' => false,
                'is_active' => true,
                'features' => [
                    Feature::MEMBER_LIMIT => ['is_countable' => true, 'usage_limit' => 20],
                    Feature::MESS_REPORT_GENERATE => ['is_countable' => true, 'usage_limit' => 10],
                    Feature::MEAL_ADD_NOTIFICATION => ['is_countable' => true, 'usage_limit' => 5],
                    Feature::BALANCE_ADD_NOTIFICATION => ['is_countable' => false],
                    Feature::PURCHASE_NOTIFICATION => ['is_countable' => false],
                ],
                'packages' => [
                    [
                        'duration' => self::DURATION_1_MONTH,
                        'price' => 9.99,
                        'is_trial' => true,
                    ],
                    [
                        'duration' => self::DURATION_6_MONTHS,
                        'price' => 49.99,
                        'is_trial' => false,
                    ],
                    [
                        'duration' => self::DURATION_12_MONTHS,
                        'price' => 99.99,
                        'is_trial' => false,
                    ],
                ],
            ],
            [
                'name' => 'Enterprise',
                'keyword' => SubPlan::ENTERPRISE,
                'is_free' => false,
                'is_active' => true,
                'features' => [
                    Feature::MEMBER_LIMIT => ['is_countable' => true, 'usage_limit' => 50],
                    Feature::MESS_REPORT_GENERATE => ['is_countable' => true, 'usage_limit' => 20],
                    Feature::MEAL_ADD_NOTIFICATION => ['is_countable' => true, 'usage_limit' => 10],
                    Feature::BALANCE_ADD_NOTIFICATION => ['is_countable' => true, 'usage_limit' => 5],
                    Feature::PURCHASE_NOTIFICATION => ['is_countable' => true, 'usage_limit' => 5],
                    Feature::FUND_ADD => ['is_countable' => false],
                    Feature::ROLE_MANAGEMENT => ['is_countable' => false],
                    Feature::PURCHASE_REQUEST => ['is_countable' => false],
                ],
                'packages' => [
                    [
                        'duration' => self::DURATION_1_MONTH,
                        'price' => 19.99,
                        'is_trial' => true,
                    ],
                    [
                        'duration' => self::DURATION_6_MONTHS,
                        'price' => 99.99,
                        'is_trial' => false,
                    ],
                    [
                        'duration' => self::DURATION_12_MONTHS,
                        'price' => 199.99,
                        'is_trial' => false,
                    ],
                ],
            ],
        ],
    ];

    public function run(): void
    {
        Plan::truncate();
        PlanPackage::truncate();
        PlanFeature::truncate();

        DB::beginTransaction();

        try {
            // Seed plans
            $plans = $this->data['plans'];

            foreach ($plans as $planData) {
                $plan = Plan::create([
                    'name' => $planData['name'],
                    'keyword' => $planData['keyword'],
                    'is_free' => $planData['is_free'],
                    'is_active' => $planData['is_active'],
                ]);

                // Seed features for the plan
                $this->createFeatures($plan, $planData['features']);

                // Seed packages for the plan
                $this->createPackages($plan, $planData['packages'], $planData['is_free'], $planData['is_active']);
            }

            // Commit the transaction
            DB::commit();
        } catch (\Exception $e) {
            // Rollback the transaction on error
            DB::rollBack();
            throw $e; // Re-throw the exception to stop the seeder
        }
    }
    private function createFeatures(Plan $plan, array $features): void
    {
        foreach ($features as $featureName => $featureConfig) {
            $plan->features()->create([
                'name' => $featureName,
                'is_countable' => $featureConfig['is_countable'],
                'usage_limit' => $featureConfig['is_countable'] ? $featureConfig['usage_limit'] : null,
            ]);
        }
    }

    private function createPackages(Plan $plan, array $packages, bool $isFree, bool $isActive): void
    {
        foreach ($packages as $package) {
            $plan->packages()->create([
                'is_trial' => $package['is_trial'],
                'is_free' => $isFree,
                'duration' => $package['duration'],
                'price' => $package['price'],
                'is_active' => $isActive,
            ]);
        }
    }
}
