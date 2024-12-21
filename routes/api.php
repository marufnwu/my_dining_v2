<?php

use App\Http\Controllers\Api\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::as("api.")->group(function () {
    Route::as("v1.")->prefix("v1")->group(base_path('routes/api_v1.php'));

    //user app
    Route::prefix("user")->controller(UserController::class)->group(function () {

        //Must Guest
        Route::post("sign-up", "createAccount");

        //Must auth
    });
});
