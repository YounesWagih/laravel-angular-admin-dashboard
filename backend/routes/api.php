<?php

use App\Http\Controllers\Api\Auth\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->controller(AuthController::class)->group(function (): void {
    Route::post('/register', 'register')->middleware('stateful.request');
    Route::post('/login', 'login')->middleware('stateful.request');

    Route::middleware(['auth:sanctum', 'active'])->group(function (): void {
        Route::get('/user', 'user');
        Route::post('/logout', 'logout')->middleware('stateful.request');
    });
});
