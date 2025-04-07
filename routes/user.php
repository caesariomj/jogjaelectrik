<?php

use App\Http\Controllers\User\CartController;
use App\Http\Controllers\User\CheckoutController;
use App\Http\Controllers\User\OrderController;
use App\Http\Controllers\User\PaymentController;
use App\Http\Controllers\User\ProfileController;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::middleware(['auth', 'role:user'])->group(function () {
    Route::get('/profil-saya', [ProfileController::class, 'index'])->middleware('password.confirm')->name('profile');

    Route::get('/pengaturan-akun', [ProfileController::class, 'setting'])->name('setting');

    Route::get('/keranjang-belanja', [CartController::class, 'index'])->name('cart');

    Route::get('/checkout', [CheckoutController::class, 'index'])->middleware('verified')->name('checkout');

    Route::prefix('pesanan')->name('orders.')->middleware('order_ownership_check')->group(function () {
        Route::get('/', [OrderController::class, 'index'])->name('index');

        Route::get('{orderNumber}/detail', [OrderController::class, 'show'])->name('show');

        Volt::route('/berhasil/{orderNumber?}', 'pages.user.order-success')->name('success');
    });

    Route::prefix('riwayat-transaksi')->name('transactions.')->group(function () {
        Route::get('/', [PaymentController::class, 'index'])->name('index');

        Route::get('{id}/detail', [PaymentController::class, 'show'])->name('show');
    });
});
