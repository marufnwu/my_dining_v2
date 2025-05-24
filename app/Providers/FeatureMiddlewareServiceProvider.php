<?php

namespace App\Providers;

use App\Constants\Feature;
use App\Http\Middleware\CheckMessFeatureAccess;
use Illuminate\Support\ServiceProvider;

class FeatureMiddlewareServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        // Register feature middlewares dynamically based on Feature constants
        $this->registerFeatureMiddlewares();
    }

    /**
     * Register feature middleware aliases for each feature constant
     *
     * @return void
     */
    protected function registerFeatureMiddlewares()
    {
        // Get all constants from the Feature class using reflection
        $reflectionClass = new \ReflectionClass(Feature::class);
        $featureConstants = $reflectionClass->getConstants();

        // For each feature constant, register a middleware alias
        foreach ($featureConstants as $name => $value) {
            // Register the middleware with the feature value
            app('router')->aliasMiddleware('mess.feature.' . $value, CheckMessFeatureAccess::class . ':' . $value);
        }

        // Register the generic mess feature middleware
        app('router')->aliasMiddleware('mess.feature', CheckMessFeatureAccess::class);
    }
}
