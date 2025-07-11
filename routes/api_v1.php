<?php

use Illuminate\Support\Facades\Route;

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

// Enhanced Notification System Routes
Route::prefix('notifications')->name('notifications.')->group(function () {
    // User notification management
    Route::get('/', [App\Http\Controllers\Api\NotificationController::class, 'index'])->name('index');
    Route::get('/stats', [App\Http\Controllers\Api\NotificationController::class, 'stats'])->name('stats');
    Route::patch('/{notification}/read', [App\Http\Controllers\Api\NotificationController::class, 'markAsRead'])->name('mark-as-read');
    Route::patch('/read-all', [App\Http\Controllers\Api\NotificationController::class, 'markAllAsRead'])->name('mark-all-as-read');

    // FCM token management
    Route::patch('/fcm-token', [App\Http\Controllers\Api\NotificationController::class, 'updateFcmToken'])->name('update-fcm-token');

    // Template and metadata endpoints
    Route::get('/templates', [App\Http\Controllers\Api\NotificationController::class, 'templates'])->name('templates');
    Route::get('/categories', [App\Http\Controllers\Api\NotificationController::class, 'categories'])->name('categories');
    Route::get('/priorities', [App\Http\Controllers\Api\NotificationController::class, 'priorities'])->name('priorities');

    // Sending notifications (admin/manager only)
    Route::middleware(['role:admin,manager'])->group(function () {
        Route::post('/send/custom', [App\Http\Controllers\Api\NotificationController::class, 'sendCustom'])->name('send-custom');
        Route::post('/send/template', [App\Http\Controllers\Api\NotificationController::class, 'sendTemplate'])->name('send-template');
        Route::post('/send/all-members', [App\Http\Controllers\Api\NotificationController::class, 'sendToAllMembers'])->name('send-to-all-members');
        Route::post('/send/roles', [App\Http\Controllers\Api\NotificationController::class, 'sendToRoles'])->name('send-to-roles');
        Route::post('/send/admins', [App\Http\Controllers\Api\NotificationController::class, 'sendToAdmins'])->name('send-to-admins');
        Route::post('/send/actionable', [App\Http\Controllers\Api\NotificationController::class, 'sendActionable'])->name('send-actionable');
        Route::post('/schedule', [App\Http\Controllers\Api\NotificationController::class, 'schedule'])->name('schedule');
    });

    // System maintenance (admin only)
    Route::middleware(['role:admin'])->group(function () {
        Route::post('/process-scheduled', [App\Http\Controllers\Api\NotificationController::class, 'processScheduled'])->name('process-scheduled');
        Route::delete('/cleanup-expired', [App\Http\Controllers\Api\NotificationController::class, 'cleanupExpired'])->name('cleanup-expired');
    });
});

