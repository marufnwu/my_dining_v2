<?php

use App\Constants\MessPermission;
use App\Constants\MessUserRole;
use App\Http\Controllers\Api\DepositController;
use App\Http\Controllers\Api\FundController;
use App\Http\Controllers\Api\MealController;
use App\Http\Controllers\Api\MessController;
use App\Http\Controllers\Api\MessManagementController;
use App\Http\Controllers\Api\MessMemberController;
use App\Http\Controllers\Api\MonthController;
use App\Http\Controllers\Api\OtherCostController;
use App\Http\Controllers\Api\PurchaseController;
use App\Http\Controllers\Api\PurchaseRequestController;
use App\Http\Controllers\Api\SummaryController;
use App\Http\Controllers\Api\UserController;
use App\Http\Middleware\CheckActiveMonth;
use App\Http\Middleware\MustNotMessJoinChecker;
use App\Models\PurchaseRequest;
use App\Services\MessSummaryService;
use Illuminate\Support\Facades\Route;

// Global API route prefix and naming
Route:: as('api.')->group(function () {

    // Version 1 API routes
    Route:: as('v1.')->prefix('v1')->group(base_path('routes/api_v1.php'));

    // Guest routes (unauthenticated users)
    Route::middleware('guest')->group(function () {

        // Authentication routes
        Route::prefix('auth')->group(function () {
            Route::post('sign-up', [UserController::class, 'createAccount'])->name('auth.signup');
            Route::post('login', [UserController::class, 'login'])->name('auth.login');
        });

        Route::prefix('country')->controller(\App\Http\Controllers\Api\CountryController::class)->group(function () {
            Route::get('list', 'countries')->name('country.list');
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
                    Route::post("inititate/add/all", "initiateAll")->middleware("MonthChecker:false");
                    Route::get("initiated/{status}", "inititatedUser")->middleware("MonthChecker:false");
                });

            Route::prefix("month")->controller(MonthController::class)->group(function () {
                Route::post("create", "createMonth");
                Route::get("list", "list");
                Route::put("change-status", "changeStatus");

                // Month details and information
                Route::get("show/{monthId?}", "show");
                Route::get("summary/{monthId?}", "summary");

                // Month management actions
                Route::post("close", "closeMonth");
                Route::post("{monthId}/duplicate", "duplicate");

                // Analytics and reporting
                Route::get("compare", "compare");
                Route::get("statistics", "statistics");
                Route::get("export/{monthId?}", "export");
                Route::get("timeline/{monthId?}", "timeline");

                // Analysis features
                Route::get("budget-analysis/{monthId?}", "budgetAnalysis");
                Route::get("validate/{monthId?}", "validate");
                Route::get("performance/{monthId?}", "performance");
            });

            Route::prefix("meal")->middleware("MonthChecker:false")->controller(MealController::class)->group(function () {
                Route::post("add", "add");
                Route::put("{meal}/update", "update");
                Route::delete("{meal}/delete", "delete");
                Route::get("list", "list");
                Route::get("/user/{messUser}/by-date", action: "getUserMealByDate");
            });

            Route::prefix("deposit")->middleware("MonthChecker:false")->controller(DepositController::class)->group(function () {
                Route::post("add", "add");
                Route::put("{deposit}/update", "update");
                Route::delete("{deposit}/delete", "delete");
                Route::get("list", "list");
                Route::get("history/{messUser}", "history");
            });

            Route::prefix("other-cost")->middleware("MonthChecker:false")->controller(OtherCostController::class)->group(function () {
                Route::post("add", "add");
                Route::put("{otherCost}/update", "update");
                Route::delete("{otherCost}/delete", "delete");
                Route::get("list", "list");
            });

            Route::prefix("purchase")
                ->middleware(["MonthChecker:false"])
                ->controller(PurchaseController::class)
                ->group(function () {
                    Route::post("add", "add");
                    Route::put("{purchase}/update", "update");
                    Route::delete("{purchase}/delete", "delete");
                    Route::get("list", "list");
                });

            Route::prefix("purchase-request")
                ->middleware(["MonthChecker:false", "mess.user:true"])
                ->controller(PurchaseRequestController::class)
                ->group(function () {
                    Route::post("add", "create");
                    Route::put("{purchaseRequest}/update", "update");
                    Route::put("{purchaseRequest}/update/status", "updateStatus");
                    Route::delete("{purchaseRequest}/delete", "delete");
                    Route::get("/", "list");
                });

            Route::prefix("fund")->middleware("MonthChecker:false")->controller(FundController::class)->group(function () {
                Route::post("add", "add");
                Route::put("{fund}/update", "update");
                Route::delete("{fund}/delete", "delete");
                Route::get("list", "list");
            });

            Route::prefix('mess')
                ->controller(MessController::class)
                ->group(function () {
                    Route::get('mess-user/{user?}', 'messUser')->name('mess.user');
                });

            // Mess Management routes (for users currently in a mess)
            Route::prefix('mess-management')
                ->controller(MessManagementController::class)
                ->group(function () {
                    Route::get('info', 'getCurrentMess');
                    Route::post('leave', 'leaveMess');
                    Route::get('incoming-requests', 'getMessJoinRequests');
                    Route::post('incoming-requests/{messRequest}/accept', 'acceptJoinRequest');
                    Route::post('incoming-requests/{messRequest}/reject', 'rejectJoinRequest');
                    Route::post('close', 'closeMess');
                });

            Route::prefix("role")->group(function () {
                //Route::get("list", "list");
            });

            Route::prefix("summary")
                ->middleware("MonthChecker:false")
                ->controller(SummaryController::class)
                ->group(function () {
                    Route::get('months/{type}', 'monthSummary')
                        ->where('type', 'minimal|details');
                    Route::get("months/user/{type}", "userSummary");
                });

        });

        // Routes that require
        Route::prefix('mess')
            ->controller(MessController::class)
            ->middleware(MustNotMessJoinChecker::class)
            ->group(function () {
                Route::post('create', 'createMess')->name('mess.create');
            });

        // Mess Management routes (for all authenticated users)
        Route::prefix('mess-management')
            ->controller(MessManagementController::class)
            ->group(function () {
                Route::get('available', 'getAvailableMesses');
                Route::post('join-request', 'sendJoinRequest');
                Route::get('join-requests', 'getUserJoinRequests');
                Route::delete('join-requests/{messRequest}', 'cancelJoinRequest');
            });

        // Authentication check route
        Route::prefix('auth')->group(function () {
            Route::get('check-login', [UserController::class, 'checkLogin'])->name('auth.check-login');
        });

        // Profile management routes
        Route::prefix('profile')->controller(UserController::class)->group(function () {
            Route::get('/', 'getProfile')->name('profile.show');
            Route::put('/', 'updateProfile')->name('profile.update');
            Route::post('avatar', 'uploadAvatar')->name('profile.avatar.upload');
            Route::delete('avatar', 'removeAvatar')->name('profile.avatar.remove');
        });
    });
});
