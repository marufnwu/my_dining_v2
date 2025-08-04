<?php

namespace App\Facades;

use App\Models\Mess;
use App\Services\FeatureService;
use App\Constants\Feature as FeatureList;
use App\Helpers\Pipeline;
use Illuminate\Support\Facades\Facade;

/**
 * @method static bool hasActiveSubscription(Mess $mess)
 * @method static \App\Helpers\Pipeline canUseFeature(Mess $mess, string $featureName)
 * @method static \App\Helpers\Pipeline incrementFeatureUsage(Mess $mess, string $featureName)
 * @method static \App\Helpers\Pipeline getAvailableFeatures(Mess $mess)
 *
 * @see \App\Services\FeatureService
 */
class Feature extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'feature';
    }

    public static function hasActiveSubscription(Mess $mess): bool
    {
        $subscription = $mess->subscription;
        return $subscription && $subscription->isActiveOrInGrace();
    }

    public static function canUseFeature(Mess $mess, string $featureName): Pipeline
    {
        $subscription = $mess->subscription;

        // If no subscription or subscription is not active, use default plan
        if (!$subscription || !$subscription->isActiveOrInGrace()) {
            return self::checkDefaultPlanFeatureAccess($mess, $featureName);
        }

        // Check with subscription
        return app(FeatureService::class)->canUseFeature($mess, $featureName);
    }

    public static function incrementFeatureUsage(Mess $mess, string $featureName): Pipeline
    {
        $subscription = $mess->subscription;

        // If no subscription or subscription is not active, handle default plan increment
        if (!$subscription || !$subscription->isActiveOrInGrace()) {
            return self::incrementDefaultPlanFeatureUsage($mess, $featureName);
        }

        // Increment with subscription
        return app(FeatureService::class)->incrementFeatureUsage($mess, $featureName);
    }

    public static function getAvailableFeatures(Mess $mess): Pipeline
    {
        $subscription = $mess->subscription;

        // If no subscription or subscription is not active, return default plan features
        if (!$subscription || !$subscription->isActiveOrInGrace()) {
            return self::getDefaultPlanFeatures($mess);
        }

        // Get features with subscription
        return app(FeatureService::class)->getAvailableFeatures($mess);
    }

    private static function checkDefaultPlanFeatureAccess(Mess $mess, string $featureName): Pipeline
    {
        $defaultPlan = \App\Models\Plan::getDefaultPlan();

        if (!$defaultPlan) {
            return Pipeline::error("Default plan not found", 500);
        }

        $feature = $defaultPlan->features()->where('name', $featureName)->first();

        if (!$feature) {
            return Pipeline::error("Feature not available in default plan", 403);
        }

        if (!$feature->is_countable) {
            return Pipeline::success(); // Feature is available without usage limits
        }

        $used = self::getDefaultPlanFeatureUsage($mess, $featureName);

        if ($used >= $feature->usage_limit) {
            return Pipeline::error("Default plan limit reached. Upgrade for more features.", 403);
        }

        return Pipeline::success([
            'used' => $used,
            'limit' => $feature->usage_limit,
            'remaining' => $feature->usage_limit - $used
        ]);
    }

    private static function getDefaultPlanFeatureUsage(Mess $mess, string $featureName): int
    {
        switch ($featureName) {
            case FeatureList::MEMBER_LIMIT:
                return $mess->messUsers()->count();
            case FeatureList::MESS_REPORT_GENERATE:
                // You can implement monthly usage tracking here
                return 0; // For now, return 0
            case FeatureList::MEAL_ADD_NOTIFICATION:
            case FeatureList::BALANCE_ADD_NOTIFICATION:
            case FeatureList::PURCHASE_NOTIFICATION:
                // You can implement monthly usage tracking here
                return 0; // For now, return 0
            default:
                return 0;
        }
    }

    private static function incrementDefaultPlanFeatureUsage(Mess $mess, string $featureName): Pipeline
    {
        // For default plan features, we don't actually increment anything
        // The usage is calculated dynamically (like member count)
        return self::checkDefaultPlanFeatureAccess($mess, $featureName);
    }

    private static function getDefaultPlanFeatures(Mess $mess): Pipeline
    {
        $defaultPlan = \App\Models\Plan::getDefaultPlan();

        if (!$defaultPlan) {
            return Pipeline::error("Default plan not found", 500);
        }

        $features = $defaultPlan->features->map(function ($feature) use ($mess) {
            $used = $feature->is_countable ? self::getDefaultPlanFeatureUsage($mess, $feature->name) : null;

            return [
                'name' => $feature->name,
                'description' => $feature->description ?? "Default plan {$feature->name}",
                'is_countable' => $feature->is_countable,
                'usage_limit' => $feature->usage_limit,
                'used' => $used,
                'remaining' => $feature->is_countable ? ($feature->usage_limit - $used) : null,
                'reset_period' => $feature->reset_period ?? 'monthly'
            ];
        });

        return Pipeline::success($features);
    }
}
