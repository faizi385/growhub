<?php

use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\StoreController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\PublicProductController;
use App\Http\Controllers\Api\V1\PublicStoreController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\AdminStoreController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

// Authentication
Route::prefix('v1/auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])
        ->middleware('throttle:10,1')
        ->name('auth.register');

    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:5,1')
        ->name('auth.login');

    Route::post('/logout', [AuthController::class, 'logout'])
        ->middleware('auth:sanctum')
        ->name('auth.logout');

    Route::get('/me', [AuthController::class, 'me'])
        ->middleware('auth:sanctum')
        ->name('auth.me');
});

// Public categories
Route::prefix('v1')->group(function () {
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/products', [PublicProductController::class, 'index']);
    Route::get('/products/{slug}', [PublicProductController::class, 'show']);
    Route::get('/stores', [PublicStoreController::class, 'index']);
    Route::get('/stores/{slug}', [PublicStoreController::class, 'show']);
});

// Authenticated seller routes
Route::middleware('auth:sanctum')
    ->prefix('v1')
    ->group(function () {
        Route::get('/my-store', [StoreController::class, 'myStore']);
        Route::put('/my-store', [StoreController::class, 'update']);
        Route::get('/my-products', [ProductController::class, 'index']);
        Route::post('/my-products', [ProductController::class, 'store']);
        Route::get('/my-products/{product}', [ProductController::class, 'show']);
        Route::put('/my-products/{product}', [ProductController::class, 'update']);
        Route::delete('/my-products/{product}', [ProductController::class, 'destroy']);
        Route::get('/seller/dashboard', [DashboardController::class, 'index']);
           Route::put('/profile', [ProfileController::class, 'update']);
        Route::put('/profile/password', [ProfileController::class, 'updatePassword']);
    });

    // Admin routes
Route::middleware(['auth:sanctum', 'admin'])
    ->prefix('v1/admin')
    ->group(function () {
        Route::get('/dashboard', [AdminStoreController::class, 'dashboard']);
        Route::get('/stores', [AdminStoreController::class, 'index']);
        Route::get('/stores/{store}', [AdminStoreController::class, 'show']);

        Route::put('/stores/{store}', [AdminStoreController::class, 'update']);

        Route::patch('/stores/{store}/approve', [AdminStoreController::class, 'approve']);
        Route::patch('/stores/{store}/reject', [AdminStoreController::class, 'reject']);
        Route::patch('/stores/{store}/suspend', [AdminStoreController::class, 'suspend']);
        Route::patch('/stores/{store}/reactivate', [AdminStoreController::class, 'reactivate']);

        Route::delete('/stores/{store}', [AdminStoreController::class, 'destroy']);
    });