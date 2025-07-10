<?php

namespace App\Providers;

use App\Console\Commands\FreshMigrateAndSeed;
use App\Rules\ActiveMessUser;
use App\Rules\UserInitiatedInCurrentMonth;
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
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Setting::observe(SettingObserver::class);

        // Register custom validation rules
        Validator::extend('active_mess_user', function ($attribute, $value, $parameters, $validator) {
            $rule = new ActiveMessUser();
            $passes = true;
            $errorMessage = '';

            $rule->validate($attribute, $value, function ($message) use (&$passes, &$errorMessage, $attribute) {
                $passes = false;
                $errorMessage = str_replace(':attribute', str_replace('_', ' ', $attribute), $message);
            });

            // If validation fails, set the custom message
            if (!$passes && $errorMessage) {
                $validator->getMessageBag()->add($attribute, $errorMessage);
            }

            return $passes;
        });

        Validator::extend('user_initiated_in_current_month', function ($attribute, $value, $parameters, $validator) {
            $rule = new UserInitiatedInCurrentMonth();
            $passes = true;
            $errorMessage = '';

            $rule->validate($attribute, $value, function ($message) use (&$passes, &$errorMessage, $attribute) {
                $passes = false;
                $errorMessage = str_replace(':attribute', str_replace('_', ' ', $attribute), $message);
            });

            // If validation fails, set the custom message
            if (!$passes && $errorMessage) {
                $validator->getMessageBag()->add($attribute, $errorMessage);
            }

            return $passes;
        });
    }
}
