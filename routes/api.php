<?php

use App\Http\Controllers\Api\UserController;
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

    Route::group(["middleware" => ["auth:sanctum", "EmailVerified", "MessJoinChecker"]], function () {

        //============User Auth Section=======================

        Route::prefix("auth")->group(function () {
            Route::get("check-login", [UserController::class, "checkLogin"]);
        });
    });

});
