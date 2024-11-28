<?php

use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

require __DIR__.'/user.php';

require __DIR__.'/admin.php';

require __DIR__.'/auth.php';

Route::get('/', [HomeController::class, 'index'])->name('home');

Volt::route('/produk', 'pages.products')->name('products');

Route::get('/produk/{slug}', [HomeController::class, 'productDetail'])->name('products.detail');
