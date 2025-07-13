<?php

namespace App\Config;

use App\Constants\Feature as FeatureList;
use Illuminate\Support\Facades\Cache;

class FeatureConfig
{
    const CACHE_KEY = 'feature_definitions';
    const CACHE_TTL = 3600; // 1 hour

    /**
     * Define all available features with their properties
     */
    public static function getFeatureDefinitions(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            // Define features statically instead of reading from database
            return [
                FeatureList::MEMBER_LIMIT => [
                    'name' => FeatureList::MEMBER_LIMIT,
                    'description' => 'Maximum number of members allowed in the mess',
                    'is_countable' => true,
                    'reset_period' => 'lifetime',
                    'free_limit' => 5,
                    'category' => 'members'
                ],
                FeatureList::MESS_REPORT_GENERATE => [
                    'name' => FeatureList::MESS_REPORT_GENERATE,
                    'description' => 'Number of reports that can be generated',
                    'is_countable' => true,
                    'reset_period' => 'monthly',
                    'free_limit' => 2,
                    'category' => 'reports'
                ],
                FeatureList::MEAL_ADD_NOTIFICATION => [
                    'name' => FeatureList::MEAL_ADD_NOTIFICATION,
                    'description' => 'Number of meal notifications that can be sent',
                    'is_countable' => true,
                    'reset_period' => 'monthly',
                    'free_limit' => 10,
                    'category' => 'notifications'
                ],
                FeatureList::BALANCE_ADD_NOTIFICATION => [
                    'name' => FeatureList::BALANCE_ADD_NOTIFICATION,
                    'description' => 'Number of balance notifications that can be sent',
                    'is_countable' => true,
                    'reset_period' => 'monthly',
                    'free_limit' => 5,
                    'category' => 'notifications'
                ],
                FeatureList::PURCHASE_NOTIFICATION => [
                    'name' => FeatureList::PURCHASE_NOTIFICATION,
                    'description' => 'Number of purchase notifications that can be sent',
                    'is_countable' => true,
                    'reset_period' => 'monthly',
                    'free_limit' => 5,
                    'category' => 'notifications'
                ],
                FeatureList::FUND_ADD => [
                    'name' => FeatureList::FUND_ADD,
                    'description' => 'Ability to add funds to the mess',
                    'is_countable' => false,
                    'reset_period' => 'lifetime',
                    'free_limit' => 0,
                    'category' => 'financial'
                ],
                FeatureList::ROLE_MANAGEMENT => [
                    'name' => FeatureList::ROLE_MANAGEMENT,
                    'description' => 'Advanced role and permission management',
                    'is_countable' => false,
                    'reset_period' => 'lifetime',
                    'free_limit' => 0,
                    'category' => 'management'
                ],
                FeatureList::PURCHASE_REQUEST => [
                    'name' => FeatureList::PURCHASE_REQUEST,
                    'description' => 'Purchase request system',
                    'is_countable' => false,
                    'reset_period' => 'lifetime',
                    'free_limit' => 0,
                    'category' => 'management'
                ]
            ];
        });
    }

    /**
     * Get feature definition by name
     */
    public static function getFeatureDefinition(string $featureName): ?array
    {
        $definitions = self::getFeatureDefinitions();
        return $definitions[$featureName] ?? null;
    }

    /**
     * Get all feature names
     */
    public static function getAllFeatureNames(): array
    {
        return array_keys(self::getFeatureDefinitions());
    }

    /**
     * Get features by category
     */
    public static function getFeaturesByCategory(string $category): array
    {
        $definitions = self::getFeatureDefinitions();
        return array_filter($definitions, function ($feature) use ($category) {
            return $feature['category'] === $category;
        });
    }

    /**
     * Get free tier limits
     */
    public static function getFreeLimits(): array
    {
        $definitions = self::getFeatureDefinitions();
        $freeLimits = [];

        foreach ($definitions as $featureName => $definition) {
            if ($definition['free_limit'] > 0) {
                $freeLimits[$featureName] = [
                    'limit' => $definition['free_limit'],
                    'reset_period' => $definition['reset_period']
                ];
            }
        }

        return $freeLimits;
    }

    /**
     * Clear feature cache
     */
    public static function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
