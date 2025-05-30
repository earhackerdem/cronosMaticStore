<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\HealthCheckController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Api\V1\Admin\ImageUploadController;
use App\Http\Controllers\Api\V1\Admin\ProductController as AdminProductController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// API v1 Routes
Route::prefix('v1')->group(function () {
    // Public routes
    Route::get('/status', [HealthCheckController::class, 'status']);

    // Rutas de autenticación
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);

    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/auth-status', [HealthCheckController::class, 'authStatus']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/user', [AuthController::class, 'user']);

        // Admin protected routes
        Route::prefix('admin')->middleware('admin')->group(function () {
            Route::apiResource('categories', AdminCategoryController::class);
            Route::post('images/upload', [ImageUploadController::class, 'store'])->name('admin.images.upload');
            Route::apiResource('products', AdminProductController::class);
        });
    });
});
