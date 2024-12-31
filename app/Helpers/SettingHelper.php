<?php
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

if (!function_exists('get_setting')) {
    function get_setting($key, $default = null)
    {
        $settings = Cache::remember('settings', 60, function () {
            return Setting::all()->keyBy('key')->map(function ($setting) {
                return $setting->value;
            })->toArray();
        });

        return $settings[$key] ?? $default;
    }
}

if (!function_exists('set_setting')) {
    function set_setting($key, $value)
    {
        $setting = Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget('settings');
        return $setting;
    }
}
