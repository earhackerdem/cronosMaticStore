<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\ProductController;

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

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
