<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\HealthCheckController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Api\V1\Admin\ImageUploadController;
use App\Http\Controllers\Api\V1\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\CartController;
use App\Http\Controllers\Api\V1\User\AddressController;

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

    // Public Category Routes
    Route::get('/categories', [CategoryController::class, 'index'])->name('api.v1.categories.index');
    Route::get('/categories/{slug}', [CategoryController::class, 'show'])->name('api.v1.categories.show');

    // Public Product Routes
    Route::get('/products', [ProductController::class, 'index'])->name('api.v1.products.index');
    Route::get('/products/{slug}', [ProductController::class, 'show'])->name('api.v1.products.show');

    // Rutas de autenticación
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);

    // Cart routes (disponibles para usuarios autenticados e invitados)
    Route::prefix('cart')->group(function () {
        Route::get('/', [CartController::class, 'show'])->name('api.v1.cart.show');
        Route::post('/items', [CartController::class, 'addItem'])->name('api.v1.cart.items.add');
        Route::put('/items/{cart_item_id}', [CartController::class, 'updateItem'])->name('api.v1.cart.items.update');
        Route::delete('/items/{cart_item_id}', [CartController::class, 'removeItem'])->name('api.v1.cart.items.remove');
        Route::delete('/', [CartController::class, 'clear'])->name('api.v1.cart.clear');
    });

    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/auth-status', [HealthCheckController::class, 'authStatus']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/user', [AuthController::class, 'user']);

        // User Address Management Routes
        Route::prefix('user')->group(function () {
            Route::apiResource('addresses', AddressController::class);
            Route::patch('/addresses/{address}/set-default', [AddressController::class, 'setDefault'])
                ->name('api.v1.user.addresses.set-default');
        });

        // Admin protected routes
        Route::prefix('admin')->middleware('admin')->group(function () {
            Route::apiResource('categories', AdminCategoryController::class);
            Route::post('images/upload', [ImageUploadController::class, 'store'])->name('admin.images.upload');
            Route::apiResource('products', AdminProductController::class);
        });
    });
});
