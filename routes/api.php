<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::as("api.")->group(function () {
    Route::as("v1.")->prefix("v1")->group(base_path('routes/api_v1.php'));

    Route::get('/user', function (Request $request) {
        return $request->user();
    })->middleware('auth:sanctum');

});


