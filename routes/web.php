<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\User\XenditWebhookController;
use Illuminate\Support\Facades\Route;

require __DIR__.'/user.php';

require __DIR__.'/admin.php';

require __DIR__.'/auth.php';

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/produk', [HomeController::class, 'products'])->name('products');

Route::get('/produk/cari', [HomeController::class, 'products'])->name('products.search');

Route::get('/produk/{category}', [HomeController::class, 'products'])->where('category', '^(?!cari$)[a-zA-Z0-9-_]+$')->name('products.category');

Route::get('/produk/{category}/{subcategory}', [HomeController::class, 'products'])->name('products.subcategory');

Route::get('/produk/{category?}/{subcategory?}/{slug}', [HomeController::class, 'productDetail'])->name('products.detail');

Route::post('/api/xendit/webhook', XenditWebhookController::class)->middleware(['validate_xendit_webhook_token', 'throttle:10,1']);

Route::match(['get', 'put', 'patch', 'delete', 'options'], '/api/xendit/webhook', function () {
    abort(404);
});

Route::get('/faq', [HomeController::class, 'faq'])->name('faq');
