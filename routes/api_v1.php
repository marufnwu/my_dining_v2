<?php

use App\Http\Controllers\Api\SubscriptionController;
use Illuminate\Support\Facades\Route;

// Root Route
Route::get("/", function(){
    return response()->json(['message' => 'API v1 Connected']);
});

// Test route to check ForceJson middleware - this should have middleware applied automatically
Route::get('/test-auto-forcejson', function(\Illuminate\Http\Request $request) {
    return response()->json([
        'accept_header' => $request->header('Accept'),
        'content_type' => $request->header('Content-Type'),
        'method' => $request->method(),
        'is_json' => $request->expectsJson(),
        'message' => 'ForceJson middleware test - AUTO applied via appendToGroup'
    ]);
});

// Test route to check ForceJson middleware - with explicit middleware
Route::get('/test-forcejson', function(\Illuminate\Http\Request $request) {
    return response()->json([
        'accept_header' => $request->header('Accept'),
        'content_type' => $request->header('Content-Type'),
        'method' => $request->method(),
        'is_json' => $request->expectsJson(),
        'message' => 'ForceJson middleware test - WITH explicit middleware'
    ]);
})->middleware('force.json');

// Test route WITHOUT ForceJson middleware
Route::get('/test-no-forcejson', function(\Illuminate\Http\Request $request) {
    return response()->json([
        'accept_header' => $request->header('Accept'),
        'content_type' => $request->header('Content-Type'),
        'method' => $request->method(),
        'is_json' => $request->expectsJson(),
        'message' => 'ForceJson middleware test - WITHOUT middleware'
    ]);
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

