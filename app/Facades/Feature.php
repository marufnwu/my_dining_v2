<?php

namespace App\Facades;

use App\Models\Mess;
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
}
