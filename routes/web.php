<?php

use Illuminate\Support\Facades\Route;

Route:: as('test.')->prefix('test')->group(base_path('routes/test.php'));


Route::get('/', function () {
    return view('welcome');
});

// Login route (required for auth redirects)
Route::get('/login', function () {
    return response()->json([
        'error' => true,
        'message' => 'Authentication required',
        'redirect_url' => '/login'
    ], 401);
})->name('login');
