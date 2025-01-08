<?php

namespace Database\Seeders;

use App\Enums\Feature;
use App\Enums\SubPlan;
use App\Models\Plan;
use App\Models\Feature as FeatureModel;
use App\Models\PlanPackage;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = config('features.plans');
        $featureData = config('features.features');

        foreach ($plans as $planData) {
            $plan = Plan::create([
                'name' => $planData['name'],
                'keyword' => $planData['keyword'],
                'is_free' => $planData['is_free'],
                'is_active' => $planData['is_active'],
            ]);

            foreach ($featureData as $feature => $data) {
                if (isset($data[$planData['keyword']])) {
                    $isCountable = $data['is_countable'];
                    $usageLimit = $data[$planData['keyword']]['usage_limit'] ?? null;


                    $plan->features()->create([
                        'plan_id' => $plan->id,
                        'feature' => $feature,
                        'is_countable' => $isCountable,
                        'usage_limit' => $usageLimit,
                    ]);
                }
            }

            foreach ($planData['packages'] as $package) {
                $plan->packages()->create([
                    'plan_id' => $plan->id,
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
