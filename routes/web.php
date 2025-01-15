<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\User\XenditWebhookController;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

require __DIR__.'/user.php';

require __DIR__.'/admin.php';

require __DIR__.'/auth.php';

Route::get('/', [HomeController::class, 'index'])->name('home');

Volt::route('/produk', 'pages.products')->name('products');

Route::get('/produk/{slug}', [HomeController::class, 'productDetail'])->name('products.detail');

Route::post('/api/xendit/webhook', XenditWebhookController::class)->middleware(['validate_xendit_webhook_token', 'throttle:10,1']);

Route::match(['get', 'put', 'patch', 'delete', 'options'], '/api/xendit/webhook', function () {
    abort(404);
});

Route::get('/faq', [HomeController::class, 'faq'])->name('faq');
