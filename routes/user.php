<?php

use App\Http\Controllers\User\CartController;
use App\Http\Controllers\User\CheckoutController;
use App\Http\Controllers\User\ProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:user'])->group(function () {
    Route::get('/akun-saya', [ProfileController::class, 'index'])->name('profile');

    Route::get('/keranjang-belanja', [CartController::class, 'index'])->name('cart');

    Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout');
});
