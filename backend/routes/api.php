<?php

use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Role\RoleController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->controller(AuthController::class)->group(function (): void {
    Route::post('/register', 'register')->middleware('stateful.request');
    Route::post('/login', 'login')->middleware('stateful.request');

    Route::middleware(['auth:sanctum', 'active'])->group(function (): void {
        Route::get('/me', 'me');
        Route::post('/logout', 'logout')->middleware('stateful.request');
    });
});

Route::middleware(['auth:sanctum', 'active', 'admin'])->group(function (): void {
    Route::get('/roles', [RoleController::class, 'index']);
    Route::post('/roles', [RoleController::class, 'store']);
    Route::get('/roles/{role}/available-entities', [RoleController::class, 'availableEntities']);
    Route::patch('/roles/{role}', [RoleController::class, 'update']);
    Route::delete('/roles/{role}', [RoleController::class, 'destroy']);
    Route::put('/roles/{role}/permissions', [RoleController::class, 'syncPermissions']);
});
