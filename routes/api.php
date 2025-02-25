<?php

use App\Constants\MessPermission;
use App\Constants\MessUserRole;
use App\Http\Controllers\Api\MessController;
use App\Http\Controllers\Api\MessMemberController;
use App\Http\Controllers\Api\MonthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Middleware\MustNotMessJoinChecker;
use Illuminate\Support\Facades\Route;

// Global API route prefix and naming
Route::as('api.')->group(function () {

    // Version 1 API routes
    Route::as('v1.')->prefix('v1')->group(base_path('routes/api_v1.php'));

    // Guest routes (unauthenticated users)
    Route::middleware('guest')->group(function () {

        // Authentication routes
        Route::prefix('auth')->group(function () {
            Route::post('sign-up', [UserController::class, 'createAccount'])->name('auth.signup');
            Route::post('login', [UserController::class, 'login'])->name('auth.login');
        });
    });

    // Authenticated routes (requires valid Sanctum token and verified email)
    Route::middleware(['auth:sanctum', 'EmailVerified'])->group(function () {

        // Routes that require the user to be part of a mess
        Route::prefix("mess")->middleware('MessJoinChecker')->group(function () {

            // Mess member management routes
            Route::prefix('member')
                ->middleware('MessPermission:' . MessUserRole::MANAGER . ',' . MessPermission::USER_MANAGEMENT)
                ->controller(MessMemberController::class)
                ->group(function () {
                    Route::post('create-and-add', 'createUserAddMess')->name('mess.member.create-and-add');
                });

            Route::prefix("month")->controller(MonthController::class)->group(function(){
                Route::post("/create", "createMonth");
            });
        });

        // Routes that require the user to NOT be part of a mess
        Route::prefix('mess')
            ->controller(MessController::class)
            ->middleware(MustNotMessJoinChecker::class)
            ->group(function () {
                Route::post('create', 'createMess')->name('mess.create');
            });

        // Authentication check route
        Route::prefix('auth')->group(function () {
            Route::get('check-login', [UserController::class, 'checkLogin'])->name('auth.check-login');
        });
    });
});
