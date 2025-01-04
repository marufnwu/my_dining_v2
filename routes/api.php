<?php

use App\Http\Controllers\Api\MessController;
use App\Http\Controllers\Api\MessMemberController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::as("api.")->group(function () {
    Route::as("v1.")->prefix("v1")->group(base_path('routes/api_v1.php'));

    //==========Must not be authenticated================

    Route::group(["middleware" => "guest"], function () {

        //============User Auth Section=======================

        Route::prefix("auth")->group(function () {
            Route::post("sign-up", [UserController::class, "createAccount"]);
            Route::post("login", [UserController::class, "login"]);
        });
    });

    //==========Must authenticated================

    Route::group(["middleware" => ["auth:sanctum", "EmailVerified"]], function () {

        //must join mess

        Route::prefix("mess")->middleware("MessJoinChecker")->group(function () {
            Route::prefix("member")->controller(MessMemberController::class)->group(function () {
                Route::post("create-and-add", "createUserAddMess");
            });
        });

        //must not join mess

        //============User Auth Section=======================
        Route::prefix("auth")->group(function () {
            Route::get("check-login", [UserController::class, "checkLogin"]);
        });

        //mess
        Route::prefix("mess")->controller(MessController::class)->group(function () {
            Route::post("create", "createMess");
        });
    });
});
