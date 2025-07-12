<?php

namespace App\Providers;

use App\Console\Commands\FreshMigrateAndSeed;
use App\Rules\ActiveMessUser;
use App\Rules\UserInitiatedInCurrentMonth;
use App\Services\FeatureService;
use Illuminate\Support\ServiceProvider;
use App\Models\Setting;
use App\Observers\SettingObserver;
use Illuminate\Support\Facades\Validator;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register FeatureService as a singleton
        $this->app->singleton('feature', function ($app) {
            return new FeatureService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Setting::observe(SettingObserver::class);

    }
}
