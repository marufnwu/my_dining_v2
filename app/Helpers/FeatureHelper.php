<?php

namespace App\Helpers;

use App\Models\Mess;

class FeatureHelper
{
    /**
     * Check if a mess has access to a feature.
     *
     * @param Mess $mess
     * @param string $feature
     * @return bool
     */
    public static function canUse(Mess $mess, $feature)
    {
        return $mess->canUseFeature($feature);
    }

    /**
     * Use a feature and record its usage.
     *
     * @param Mess $mess
     * @param string $feature
     * @param int $amount
     * @return bool
     */
    public static function use(Mess $mess, $feature, $amount = 1)
    {
        return $mess->useFeature($feature, $amount);
    }

    /**
     * Get remaining usage count for a feature.
     *
     * @param Mess $mess
     * @param string $feature
     * @return int|null
     */
    public static function remaining(Mess $mess, $feature)
    {
        $subscription = $mess->activeSubscription;

        if (!$subscription) {
            return 0;
        }

        $planFeature = $subscription->plan->features()
            ->where('name', $feature)
            ->first();

        if (!$planFeature || !$planFeature->is_countable) {
            return null;
        }

        $usage = $subscription->featureUsages()
            ->where('plan_feature_id', $planFeature->id)
            ->first();

        if (!$usage) {
            return 0;
        }

        return $planFeature->usage_limit - $usage->used_count;
    }
}
