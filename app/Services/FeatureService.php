<?php

namespace App\Services;

use App\Helpers\Pipeline;
use App\Models\Mess;
use App\Models\PlanFeature;
use App\Models\FeatureUsage;
use App\Models\Subscription;
use App\Constants\Feature as FeatureList;
use App\Config\FeatureConfig;
use Carbon\Carbon;
use App\Models\Plan;

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

        // If no subscription, use default plan
        if (!$subscription) {
            return $this->checkDefaultPlanFeatureAccess($mess, $featureName);
        }

        // Check with subscription
        return $this->checkFeatureAccess($subscription, $featureName);
    }

    /**
     * Check default plan feature access
     */
    private function checkDefaultPlanFeatureAccess(Mess $mess, string $featureName): Pipeline
    {
        $defaultPlan = Plan::getDefaultPlan();

        if (!$defaultPlan) {
            return Pipeline::error("Default plan not found", 500);
        }

        $feature = $defaultPlan->features()->where('name', $featureName)->first();

        if (!$feature) {
            return Pipeline::error("Feature not available in default plan", 403);
        }

        if (!$feature->is_countable) {
            return Pipeline::success([], "Feature available in default plan");
        }

        $used = $this->getDefaultPlanFeatureUsage($mess, $featureName);

        if ($used >= $feature->usage_limit) {
            return Pipeline::error("Default plan limit reached. Upgrade for more features.", 403);
        }

        return Pipeline::success([
            'used' => $used,
            'limit' => $feature->usage_limit,
            'remaining' => $feature->usage_limit - $used,
            'reset_period' => $feature->reset_period ?? 'monthly'
        ], "Feature available in default plan");
    }

    /**
     * Get default plan feature usage
     */
    private function getDefaultPlanFeatureUsage(Mess $mess, string $featureName): int
    {
        switch ($featureName) {
            case FeatureList::MEMBER_LIMIT:
                // Lifetime feature - no reset needed
                return $mess->messUsers()->count();

            case FeatureList::MESS_REPORT_GENERATE:
                // Monthly reset - you can track this in a separate table
                return $this->getMonthlyUsage($mess, 'reports');

            case FeatureList::MEAL_ADD_NOTIFICATION:
                return $this->getMonthlyUsage($mess, 'meal_notifications');

            case FeatureList::BALANCE_ADD_NOTIFICATION:
                return $this->getMonthlyUsage($mess, 'balance_notifications');

            case FeatureList::PURCHASE_NOTIFICATION:
                return $this->getMonthlyUsage($mess, 'purchase_notifications');

            default:
                return 0;
        }
    }

    /**
     * Get monthly usage for a feature (you can implement this based on your needs)
     */
    private function getMonthlyUsage(Mess $mess, string $featureType): int
    {
        // This is a placeholder - you can implement based on your tracking needs
        // For example, you could have a separate table to track monthly usage
        return 0; // For now, return 0
    }

    /**
     * Get reset message based on period
     */
    private function getResetMessage(string $resetPeriod): string
    {
        switch ($resetPeriod) {
            case 'monthly':
                return 'Limit resets monthly.';
            case 'yearly':
                return 'Limit resets yearly.';
            case 'weekly':
                return 'Limit resets weekly.';
            case 'daily':
                return 'Limit resets daily.';
            case 'lifetime':
                return 'This is a lifetime limit.';
            default:
                return 'Upgrade for more features.';
        }
    }

    private function checkFeatureAccess(Subscription $subscription, string $featureName): Pipeline
    {
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
            [
                'used' => 0,
                'reset_period' => $feature->reset_period ?? $feature->default_reset_period,
                'reset_at' => $this->calculateNextResetDate($feature->reset_period ?? $feature->default_reset_period)
            ]
        );

        // Check if reset is needed
        $usage->checkAndResetIfNeeded();

        if ($usage->used >= $feature->usage_limit) {
            $resetMessage = $this->getResetMessage($usage->reset_period);
            return Pipeline::error("Feature usage limit exceeded. {$resetMessage}");
        }

        return Pipeline::success(data: [
            'remaining' => $feature->usage_limit - $usage->used,
            'limit' => $feature->usage_limit,
            'used' => $usage->used,
            'reset_period' => $usage->reset_period,
            'next_reset' => $usage->reset_at
        ]);
    }

    /**
     * Calculate next reset date
     */
    private function calculateNextResetDate(string $resetPeriod): ?Carbon
    {
        switch ($resetPeriod) {
            case 'monthly':
                return now()->addMonth();
            case 'yearly':
                return now()->addYear();
            case 'weekly':
                return now()->addWeek();
            case 'daily':
                return now()->addDay();
            case 'lifetime':
            default:
                return null; // Never reset
        }
    }

    /**
     * Increment feature usage count
     */
    public function incrementFeatureUsage(Mess $mess, string $featureName): Pipeline
    {
        $subscription = $mess->subscription;

        // If no subscription, handle default plan increment
        if (!$subscription) {
            return $this->incrementDefaultPlanFeatureUsage($mess, $featureName);
        }

        if (!$subscription->isActiveOrInGrace()) {
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
            [
                'used' => 0,
                'reset_period' => $feature->reset_period ?? $feature->default_reset_period,
                'reset_at' => $this->calculateNextResetDate($feature->reset_period ?? $feature->default_reset_period)
            ]
        );

        // Check if reset is needed
        $usage->checkAndResetIfNeeded();

        if ($usage->used >= $feature->usage_limit) {
            $resetMessage = $this->getResetMessage($usage->reset_period);
            return Pipeline::error("Feature usage limit exceeded. {$resetMessage}");
        }

        $usage->incrementUsage();

        return Pipeline::success(data: [
            'remaining' => $feature->usage_limit - $usage->used,
            'limit' => $feature->usage_limit,
            'used' => $usage->used,
            'reset_period' => $usage->reset_period,
            'next_reset' => $usage->reset_at
        ]);
    }

    /**
     * Increment default plan feature usage
     */
    private function incrementDefaultPlanFeatureUsage(Mess $mess, string $featureName): Pipeline
    {
        // For default plan features, we don't actually increment anything
        // The usage is calculated dynamically (like member count)
        // This method exists for consistency with the subscription flow

        $defaultPlan = Plan::getDefaultPlan();

        if (!$defaultPlan) {
            return Pipeline::error("Default plan not found", 500);
        }

        $feature = $defaultPlan->features()->where('name', $featureName)->first();

        if (!$feature) {
            return Pipeline::error("Feature not available in default plan", 403);
        }

        if (!$feature->is_countable) {
            return Pipeline::success([], "Feature usage recorded for default plan");
        }

        $used = $this->getDefaultPlanFeatureUsage($mess, $featureName);

        if ($used >= $feature->usage_limit) {
            return Pipeline::error("Default plan limit reached. Upgrade for more features.", 403);
        }

        return Pipeline::success([
            'used' => $used,
            'limit' => $feature->usage_limit,
            'remaining' => $feature->usage_limit - $used,
            'reset_period' => $feature->reset_period ?? 'monthly'
        ], "Feature usage recorded for default plan");
    }

    /**
     * Get all features available for a mess
     */
    public function getAvailableFeatures(Mess $mess): Pipeline
    {
        $subscription = $mess->subscription;

        // If no subscription, return default plan features
        if (!$subscription || !$subscription->isActiveOrInGrace()) {
            return $this->getDefaultPlanFeatures($mess);
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
                        [
                            'used' => 0,
                            'reset_period' => $feature->reset_period ?? $feature->default_reset_period,
                            'reset_at' => $this->calculateNextResetDate($feature->reset_period ?? $feature->default_reset_period)
                        ]
                    );

                    $usage->checkAndResetIfNeeded();
                }

                return [
                    'name' => $feature->name,
                    'description' => $feature->description,
                    'is_countable' => $feature->is_countable,
                    'usage_limit' => $feature->usage_limit,
                    'used' => $usage ? $usage->used : null,
                    'remaining' => $usage ? $usage->remaining : null,
                    'reset_period' => $usage ? $usage->reset_period : null,
                    'next_reset' => $usage ? $usage->reset_at : null
                ];
            });

        return Pipeline::success(data: $features);
    }

    /**
     * Get default plan features
     */
    private function getDefaultPlanFeatures(Mess $mess): Pipeline
    {
        $defaultPlan = Plan::getDefaultPlan();

        if (!$defaultPlan) {
            return Pipeline::error("Default plan not found", 500);
        }

        $features = $defaultPlan->features->map(function ($feature) use ($mess) {
            $used = $feature->is_countable ? $this->getDefaultPlanFeatureUsage($mess, $feature->name) : null;

            return [
                'name' => $feature->name,
                'description' => $feature->description ?? "Default plan {$feature->name}",
                'is_countable' => $feature->is_countable,
                'usage_limit' => $feature->usage_limit,
                'used' => $used,
                'remaining' => $feature->is_countable ? ($feature->usage_limit - $used) : null,
                'reset_period' => $feature->reset_period ?? 'monthly',
                'next_reset' => null
            ];
        });

        return Pipeline::success(data: $features);
    }
}
