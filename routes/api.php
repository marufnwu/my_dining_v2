<?php

use App\Constants\MessPermission;
use App\Constants\MessUserRole;
use App\Http\Controllers\Api\DepositController;
use App\Http\Controllers\Api\FundController;
use App\Http\Controllers\Api\MealController;
use App\Http\Controllers\Api\MessController;
use App\Http\Controllers\Api\MessMemberController;
use App\Http\Controllers\Api\MonthController;
use App\Http\Controllers\Api\OtherCostController;
use App\Http\Controllers\Api\PurchaseController;
use App\Http\Controllers\Api\UserController;
use App\Http\Middleware\CheckActiveMonth;
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
        Route::middleware('MessJoinChecker')->group(function () {

            // Mess member management routes
            Route::prefix('member')
                ->controller(MessMemberController::class)
                ->group(function () {
                    Route::get("list", "list");
                    Route::post('create-and-add', 'createUserAddMess')->middleware('MessPermission:' . MessPermission::USER_ADD . ',' . MessPermission::USER_MANAGEMENT)->name('mess.member.create-and-add');
                    Route::post("inititate/add/{messUser}", "initiateUser")->middleware("MonthChecker:true");
                    Route::post("inititate/add/all", "initiateAll")->middleware("MonthChecker:true");
                    Route::get("inititated/{status}", "inititatedUser")->middleware("MonthChecker:true")->where('status', 'true|false');
                    Route::get("inititate/not", "notInititated")->middleware("MonthChecker:true");
                });

            Route::prefix("month")->controller(MonthController::class)->group(function () {
                Route::post("create", "createMonth");
                Route::get("list", "list");
            });

            Route::prefix("meal")->middleware("MonthChecker:true")->controller(MealController::class)->group(function () {
                Route::post("add", "add");
                Route::put("{meal}/update", "update");
                Route::delete("{meal}/delete", "delete");
                Route::get("list", "list");
            });

            Route::prefix("deposit")->middleware("MonthChecker:true")->controller(DepositController::class)->group(function () {
                Route::post("add", "add");
                Route::put("{deposit}/update", "update");
                Route::delete("{deposit}/delete", "delete");
                Route::get("list", "list");
            });

            Route::prefix("other-cost")->middleware("MonthChecker:true")->controller(OtherCostController::class)->group(function () {
                Route::post("add", "add");
                Route::put("{otherCost}/update", "update");
                Route::delete("{otherCost}/delete", "delete");
                Route::get("list", "list");
            });

            Route::prefix("fund")->middleware("MonthChecker:true")->controller(FundController::class)->group(function () {
                Route::post("add", "add");
                Route::put("{fund}/update", "update");
                Route::delete("{fund}/delete", "delete");
                Route::get("list", "list");
            });




            Route::prefix("role")->group(function () {
                //Route::get("list", "list");
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
