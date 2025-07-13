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

        // If no subscription or subscription is not active, provide free tier access
        if (!$subscription || !$subscription->isActiveOrInGrace()) {
            return self::checkFreeFeatureAccess($mess, $featureName);
        }

        // Check with subscription
        return app(FeatureService::class)->canUseFeature($mess, $featureName);
    }

    public static function incrementFeatureUsage(Mess $mess, string $featureName): Pipeline
    {
        $subscription = $mess->subscription;

        // If no subscription or subscription is not active, handle free tier increment
        if (!$subscription || !$subscription->isActiveOrInGrace()) {
            return self::incrementFreeFeatureUsage($mess, $featureName);
        }

        // Increment with subscription
        return app(FeatureService::class)->incrementFeatureUsage($mess, $featureName);
    }

    public static function getAvailableFeatures(Mess $mess): Pipeline
    {
        $subscription = $mess->subscription;

        // If no subscription or subscription is not active, return free tier features
        if (!$subscription || !$subscription->isActiveOrInGrace()) {
            return self::getFreeFeatures($mess);
        }

        // Get features with subscription
        return app(FeatureService::class)->getAvailableFeatures($mess);
    }

    private static function checkFreeFeatureAccess(Mess $mess, string $featureName): Pipeline
    {
        // Define free tier feature limits
        $freeLimits = [
            FeatureList::MEMBER_LIMIT => 5,
            FeatureList::MESS_REPORT_GENERATE => 2,
            FeatureList::MEAL_ADD_NOTIFICATION => 10,
            FeatureList::BALANCE_ADD_NOTIFICATION => 5,
            FeatureList::PURCHASE_NOTIFICATION => 5,
        ];

        if (!isset($freeLimits[$featureName])) {
            return Pipeline::error("Feature not available in free tier", 403);
        }

        $limit = $freeLimits[$featureName];
        $used = self::getFreeFeatureUsage($mess, $featureName);

        if ($used >= $limit) {
            return Pipeline::error("Free tier limit reached. Upgrade for more features.", 403);
        }

        return Pipeline::success([
            'used' => $used,
            'limit' => $limit,
            'remaining' => $limit - $used
        ]);
    }

    private static function getFreeFeatureUsage(Mess $mess, string $featureName): int
    {
        switch ($featureName) {
            case FeatureList::MEMBER_LIMIT:
                return $mess->messUsers()->count();
            default:
                return 0; // Track other features as needed
        }
    }

    private static function incrementFreeFeatureUsage(Mess $mess, string $featureName): Pipeline
    {
        // For free tier features, we don't actually increment anything
        // The usage is calculated dynamically (like member count)
        return self::checkFreeFeatureAccess($mess, $featureName);
    }

    private static function getFreeFeatures(Mess $mess): Pipeline
    {
        $freeLimits = [
            FeatureList::MEMBER_LIMIT => 5,
            FeatureList::MESS_REPORT_GENERATE => 2,
            FeatureList::MEAL_ADD_NOTIFICATION => 10,
            FeatureList::BALANCE_ADD_NOTIFICATION => 5,
            FeatureList::PURCHASE_NOTIFICATION => 5,
        ];

        $features = [];
        foreach ($freeLimits as $featureName => $limit) {
            $used = self::getFreeFeatureUsage($mess, $featureName);
            $features[] = [
                'name' => $featureName,
                'description' => "Free tier {$featureName}",
                'is_countable' => true,
                'usage_limit' => $limit,
                'used' => $used,
                'remaining' => $limit - $used,
                'reset_period' => $featureName === FeatureList::MEMBER_LIMIT ? 'lifetime' : 'monthly'
            ];
        }

        return Pipeline::success(collect($features));
    }
}
