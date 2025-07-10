<?php

namespace App\Services;

use App\Helpers\Pipeline;
use App\Models\Mess;
use App\Models\PlanFeature;
use App\Models\FeatureUsage;
use App\Models\Subscription;
use Carbon\Carbon;

class FeatureService
{
    /**
     * Check if a mess has an active subscription
     */
    public function hasActiveSubscription(Mess $mess): bool
    {
        $subscription = $mess->subscription;

        if (!$subscription) {
            return false;
        }

        return $subscription->isActiveOrInGrace();
    }

    /**
     * Check if a mess has access to a specific feature
     */
    public function canUseFeature(Mess $mess, string $featureName): Pipeline
    {
        $subscription = $mess->subscription;

        if (!$subscription || !$subscription->isActiveOrInGrace()) {
            return Pipeline::error('No active subscription found');
        }

        $feature = $subscription->plan->features()
            ->where('name', $featureName)
            ->where('is_active', true)
            ->first();

        if (!$feature) {
            return Pipeline::error('Feature not available in current plan');
        }

        if (!$feature->is_countable) {
            return Pipeline::success(); // Feature is available without usage limits
        }

        // Check usage limits for countable features
        $usage = FeatureUsage::firstOrCreate(
            [
                'subscription_id' => $subscription->id,
                'plan_feature_id' => $feature->id
            ],
            ['used' => 0]
        );

        if ($usage->used >= $feature->usage_limit) {
            return Pipeline::error('Feature usage limit exceeded');
        }

        return Pipeline::success(data: [
            'remaining' => $feature->usage_limit - $usage->used,
            'limit' => $feature->usage_limit
        ]);
    }

    /**
     * Increment feature usage count
     */
    public function incrementFeatureUsage(Mess $mess, string $featureName): Pipeline
    {
        $subscription = $mess->subscription;

        if (!$subscription || !$subscription->isActiveOrInGrace()) {
            return Pipeline::error('No active subscription found');
        }

        $feature = $subscription->plan->features()
            ->where('name', $featureName)
            ->where('is_active', true)
            ->first();

        if (!$feature) {
            return Pipeline::error('Feature not available in current plan');
        }

        if (!$feature->is_countable) {
            return Pipeline::success(); // No need to track usage for uncountable features
        }

        $usage = FeatureUsage::firstOrCreate(
            [
                'subscription_id' => $subscription->id,
                'plan_feature_id' => $feature->id
            ],
            ['used' => 0]
        );

        if ($usage->used >= $feature->usage_limit) {
            return Pipeline::error('Feature usage limit exceeded');
        }

        $usage->increment('used');

        return Pipeline::success(data: [
            'remaining' => $feature->usage_limit - $usage->used,
            'limit' => $feature->usage_limit
        ]);
    }

    /**
     * Get all features available for a mess
     */
    public function getAvailableFeatures(Mess $mess): Pipeline
    {
        $subscription = $mess->subscription;

        if (!$subscription || !$subscription->isActiveOrInGrace()) {
            return Pipeline::error('No active subscription found');
        }

        $features = $subscription->plan->features()
            ->where('is_active', true)
            ->get()
            ->map(function ($feature) use ($subscription) {
                $usage = null;
                if ($feature->is_countable) {
                    $usage = FeatureUsage::firstOrCreate(
                        [
                            'subscription_id' => $subscription->id,
                            'plan_feature_id' => $feature->id
                        ],
                        ['used' => 0]
                    );
                }

                return [
                    'name' => $feature->name,
                    'description' => $feature->description,
                    'is_countable' => $feature->is_countable,
                    'usage_limit' => $feature->usage_limit,
                    'used' => $usage ? $usage->used : null,
                    'remaining' => $usage ? ($feature->usage_limit - $usage->used) : null
                ];
            });

        return Pipeline::success(data: $features);
    }
}
