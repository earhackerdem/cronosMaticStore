<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\Api\V1\User\AddressController as ApiAddressController;
use App\Http\Controllers\Web\UserOrderController as WebUserOrderController;
use App\Http\Controllers\PaymentReturnController;

Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

// Rutas públicas de productos
Route::get('/productos', [ProductController::class, 'index'])->name('web.products.index');
Route::get('/productos/{slug}', [ProductController::class, 'show'])->name('web.products.show');

// Ruta del carrito
Route::get('/carrito', function () {
    return Inertia::render('Cart/Index');
})->name('web.cart.index');

// Ruta del checkout
Route::get('/checkout', function () {
    return Inertia::render('Checkout/Index');
})->name('web.checkout.index');

// Ruta de confirmación de orden
Route::get('/orders/confirmation/{orderNumber}', function ($orderNumber) {
    return Inertia::render('Orders/Confirmation', [
        'orderNumber' => $orderNumber
    ]);
})->name('web.orders.confirmation');

// PayPal return routes
Route::get('/orders/payment/success', [PaymentReturnController::class, 'success'])->name('orders.payment.success');
Route::get('/orders/payment/cancel', [PaymentReturnController::class, 'cancel'])->name('orders.payment.cancel');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');

    // User orders routes
    Route::get('/user/orders', function () {
        return Inertia::render('User/UserOrdersPage');
    })->name('user.orders.index');

    Route::get('/user/orders/{orderNumber}', function ($orderNumber) {
        return Inertia::render('User/UserOrderDetailPage', [
            'orderNumber' => $orderNumber
        ]);
    })->name('user.orders.show');

    // AJAX routes for user orders data
    Route::prefix('ajax/user')->group(function () {
        Route::get('/orders', [WebUserOrderController::class, 'index'])->name('ajax.user.orders.index');
        Route::get('/orders/{order_number}', [WebUserOrderController::class, 'show'])->name('ajax.user.orders.show');
    });

    // Address management routes (API-style but with web authentication)
    Route::prefix('api/v1/user')->group(function () {
        Route::get('/addresses', [ApiAddressController::class, 'index']);
        Route::post('/addresses', [ApiAddressController::class, 'store']);
        Route::get('/addresses/{address}', [ApiAddressController::class, 'show']);
        Route::put('/addresses/{address}', [ApiAddressController::class, 'update']);
        Route::patch('/addresses/{address}', [ApiAddressController::class, 'update']);
        Route::delete('/addresses/{address}', [ApiAddressController::class, 'destroy']);
        Route::patch('/addresses/{address}/set-default', [ApiAddressController::class, 'setDefault']);
    });
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
