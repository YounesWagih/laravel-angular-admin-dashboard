<?php

use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Category\CategoryController;
use App\Http\Controllers\Api\Dashboard\DashboardController;
use App\Http\Controllers\Api\Product\ProductController;
use App\Http\Controllers\Api\Role\RoleController;
use App\Http\Controllers\Api\User\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->controller(AuthController::class)->group(function (): void {
    Route::post('/register', 'register');
    Route::post('/login', 'login');

    Route::middleware(['auth:sanctum', 'active'])->group(function (): void {
        Route::get('/me', 'me');
        Route::post('/logout', 'logout');
    });
});

Route::middleware(['auth:sanctum', 'active', 'admin'])->group(function (): void {
    Route::get('/dashboard', DashboardController::class);

    Route::get('/roles', [RoleController::class, 'index']);
    Route::post('/roles', [RoleController::class, 'store']);
    Route::get('/roles/{role}/available-entities', [RoleController::class, 'availableEntities']);
    Route::patch('/roles/{role}', [RoleController::class, 'update']);
    Route::patch('/roles/{role}/default', [RoleController::class, 'setDefault']);
    Route::delete('/roles/{role}', [RoleController::class, 'destroy']);
    Route::put('/roles/{role}/permissions', [RoleController::class, 'syncPermissions']);

    Route::get('/users', [UserController::class, 'index']);
    Route::post('/users', [UserController::class, 'store']);
    Route::get('/users/{user}', [UserController::class, 'show']);
    Route::patch('/users/{user}', [UserController::class, 'update']);
    Route::patch('/users/{user}/status', [UserController::class, 'updateStatus']);
});

Route::middleware(['auth:sanctum', 'active'])->group(function (): void {
    Route::get('/categories', [CategoryController::class, 'index'])
        ->middleware('can:categories.read');
    Route::post('/categories', [CategoryController::class, 'store'])
        ->middleware('can:categories.create');
    Route::get('/categories/{category}', [CategoryController::class, 'show'])
        ->middleware('can:categories.read');
    Route::patch('/categories/{category}', [CategoryController::class, 'update'])
        ->middleware('can:categories.update');
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])
        ->middleware('can:categories.delete');

    Route::get('/products', [ProductController::class, 'index'])
        ->middleware('can:products.read');
    Route::get('/products/options', [ProductController::class, 'options'])
        ->middleware('can:products.read');
    Route::post('/products', [ProductController::class, 'store'])
        ->middleware('can:products.create');
    Route::get('/products/{product}', [ProductController::class, 'show'])
        ->middleware('can:products.read');
    Route::patch('/products/{product}', [ProductController::class, 'update'])
        ->middleware('can:products.update');
    Route::delete('/products/{product}', [ProductController::class, 'destroy'])
        ->middleware('can:products.delete');
});
