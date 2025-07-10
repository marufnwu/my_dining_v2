<?php

use App\Http\Controllers\Api\SubscriptionController;
use Illuminate\Support\Facades\Route;

// Root Route
Route::get("/", function(){
    return "Connected";
});

// Subscription Management Routes
Route::prefix('subscriptions')->group(function () {
    Route::get('status', [SubscriptionController::class, 'getStatus']);
    Route::get('features', [SubscriptionController::class, 'getFeatures']);
    Route::get('usage', [SubscriptionController::class, 'getUsageStats']);
    Route::post('upgrade', [SubscriptionController::class, 'upgrade']);
    Route::post('cancel', [SubscriptionController::class, 'cancel']);
    Route::post('resume', [SubscriptionController::class, 'resume']);
});

