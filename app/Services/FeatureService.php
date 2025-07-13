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

        // If no subscription, use free tier limits
        if (!$subscription) {
            return $this->checkFreeFeatureAccess($mess, $featureName);
        }

        // Check with subscription
        return $this->checkFeatureAccess($subscription, $featureName);
    }

    /**
     * Check free tier feature access
     */
    private function checkFreeFeatureAccess(Mess $mess, string $featureName): Pipeline
    {
        $freeLimits = FeatureConfig::getFreeLimits();

        if (!isset($freeLimits[$featureName])) {
            return Pipeline::error("Feature not available in free tier", 403);
        }

        $limit = $freeLimits[$featureName]['limit'];
        $resetPeriod = $freeLimits[$featureName]['reset_period'];
        $used = $this->getFreeFeatureUsage($mess, $featureName, $resetPeriod);

        if ($used >= $limit) {
            $resetMessage = $this->getResetMessage($resetPeriod);
            return Pipeline::error("Free tier limit reached. {$resetMessage}", 403);
        }

        return Pipeline::success([
            'used' => $used,
            'limit' => $limit,
            'remaining' => $limit - $used,
            'reset_period' => $resetPeriod
        ], "Feature available in free tier");
    }

    /**
     * Get free tier feature usage with reset logic
     */
    private function getFreeFeatureUsage(Mess $mess, string $featureName, string $resetPeriod): int
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

        // If no subscription, handle free tier increment
        if (!$subscription) {
            return $this->incrementFreeFeatureUsage($mess, $featureName);
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
     * Increment free tier feature usage
     */
    private function incrementFreeFeatureUsage(Mess $mess, string $featureName): Pipeline
    {
        // For free tier features, we don't actually increment anything
        // The usage is calculated dynamically (like member count)
        // This method exists for consistency with the subscription flow

        $freeLimits = FeatureConfig::getFreeLimits();

        if (!isset($freeLimits[$featureName])) {
            return Pipeline::error("Feature not available in free tier", 403);
        }

        $limit = $freeLimits[$featureName]['limit'];
        $resetPeriod = $freeLimits[$featureName]['reset_period'];
        $used = $this->getFreeFeatureUsage($mess, $featureName, $resetPeriod);

        if ($used >= $limit) {
            $resetMessage = $this->getResetMessage($resetPeriod);
            return Pipeline::error("Free tier limit reached. {$resetMessage}", 403);
        }

        return Pipeline::success([
            'used' => $used,
            'limit' => $limit,
            'remaining' => $limit - $used,
            'reset_period' => $resetPeriod
        ], "Feature usage recorded for free tier");
    }

    /**
     * Get all features available for a mess
     */
    public function getAvailableFeatures(Mess $mess): Pipeline
    {
        $subscription = $mess->subscription;

        // If no subscription, return free tier features
        if (!$subscription || !$subscription->isActiveOrInGrace()) {
            return $this->getFreeFeatures($mess);
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
     * Get free tier features using configuration
     */
    private function getFreeFeatures(Mess $mess): Pipeline
    {
        $freeLimits = FeatureConfig::getFreeLimits();
        $freeFeatures = [];

        foreach ($freeLimits as $featureName => $limitConfig) {
            $featureDefinition = FeatureConfig::getFeatureDefinition($featureName);

            $freeFeatures[] = [
                'name' => $featureName,
                'description' => $featureDefinition['description'],
                'is_countable' => $featureDefinition['is_countable'],
                'usage_limit' => $limitConfig['limit'],
                'reset_period' => $limitConfig['reset_period'],
                'used' => $this->getFreeFeatureUsage($mess, $featureName, $limitConfig['reset_period']),
                'remaining' => $limitConfig['limit'] - $this->getFreeFeatureUsage($mess, $featureName, $limitConfig['reset_period'])
            ];
        }

        return Pipeline::success(data: collect($freeFeatures));
    }
}
