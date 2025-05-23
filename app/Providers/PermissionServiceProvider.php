<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use App\Services\PermissionService;

class PermissionServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('permission', function ($app) {
            return new PermissionService();
        });
    }


    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
