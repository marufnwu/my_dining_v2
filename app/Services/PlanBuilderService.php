<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\PlanPackage;
use App\Models\PlanFeature;
use App\Config\FeatureConfig;
use App\Helpers\Pipeline;
use Illuminate\Support\Facades\Schema;

class PlanBuilderService
{
    /**
     * Create a new plan with features
     */
    public function createPlan(array $planData, array $featureLimits): Pipeline
    {
        try {
            // Create the plan
            $plan = Plan::create([
                'name' => $planData['name'],
                'keyword' => $planData['keyword'],
                'description' => $planData['description'],
                'is_active' => $planData['is_active'] ?? true
            ]);

            // Create plan features based on feature limits
            $this->createPlanFeatures($plan, $featureLimits);

            return Pipeline::success($plan, 'Plan created successfully');
        } catch (\Exception $e) {
            return Pipeline::error('Failed to create plan: ' . $e->getMessage());
        }
    }

    /**
     * Create plan features
     */
    private function createPlanFeatures(Plan $plan, array $featureLimits): void
    {
        foreach ($featureLimits as $featureName => $limit) {
            $featureDefinition = FeatureConfig::getFeatureDefinition($featureName);

            if (!$featureDefinition) {
                continue; // Skip unknown features
            }

            $featureData = [
                'plan_id' => $plan->id,
                'name' => $featureDefinition['name'],
                'description' => $featureDefinition['description'],
                'is_countable' => $featureDefinition['is_countable'],
                'usage_limit' => $limit,
                'reset_period' => $featureDefinition['reset_period'],
                'is_active' => true
            ];

            // Add category if column exists
            if (Schema::hasColumn('plan_features', 'category')) {
                $featureData['category'] = $featureDefinition['category'];
            }

            PlanFeature::create($featureData);
        }
    }

    /**
     * Update plan features
     */
    public function updatePlanFeatures(Plan $plan, array $featureLimits): Pipeline
    {
        try {
            // Delete existing features
            $plan->features()->delete();

            // Create new features
            $this->createPlanFeatures($plan, $featureLimits);

            return Pipeline::success($plan, 'Plan features updated successfully');
        } catch (\Exception $e) {
            return Pipeline::error('Failed to update plan features: ' . $e->getMessage());
        }
    }

    /**
     * Create a plan package
     */
    public function createPlanPackage(Plan $plan, array $packageData): Pipeline
    {
        try {
            $package = $plan->packages()->create([
                'name' => $packageData['name'],
                'price' => $packageData['price'],
                'duration' => $packageData['duration'],
                'is_trial' => $packageData['is_trial'] ?? false,
                'is_active' => $packageData['is_active'] ?? true
            ]);

            return Pipeline::success($package, 'Package created successfully');
        } catch (\Exception $e) {
            return Pipeline::error('Failed to create package: ' . $e->getMessage());
        }
    }

    /**
     * Get available features for plan creation
     */
    public function getAvailableFeatures(): array
    {
        return FeatureConfig::getFeatureDefinitions();
    }

    /**
     * Validate feature limits
     */
    public function validateFeatureLimits(array $featureLimits): Pipeline
    {
        $errors = [];
        $availableFeatures = FeatureConfig::getAllFeatureNames();

        foreach ($featureLimits as $featureName => $limit) {
            if (!in_array($featureName, $availableFeatures)) {
                $errors[] = "Unknown feature: {$featureName}";
                continue;
            }

            $featureDefinition = FeatureConfig::getFeatureDefinition($featureName);

            if ($featureDefinition['is_countable'] && (!is_numeric($limit) || $limit < 0)) {
                $errors[] = "Invalid limit for {$featureName}: must be a positive number";
            }
        }

        if (!empty($errors)) {
            return Pipeline::error('Validation failed', 422, $errors);
        }

        return Pipeline::success(null, 'Feature limits are valid');
    }

    /**
     * Get plan features with usage information
     */
    public function getPlanFeaturesWithUsage(Plan $plan): array
    {
        return $plan->features()
            ->where('is_active', true)
            ->get()
            ->map(function ($feature) {
                $featureDefinition = FeatureConfig::getFeatureDefinition($feature->name);

                return [
                    'id' => $feature->id,
                    'name' => $feature->name,
                    'description' => $feature->description,
                    'is_countable' => $feature->is_countable,
                    'usage_limit' => $feature->usage_limit,
                    'reset_period' => $feature->reset_period,
                    'category' => $featureDefinition['category'] ?? 'general'
                ];
            })
            ->toArray();
    }
}
