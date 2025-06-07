<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\User\XenditWebhookController;
use Illuminate\Support\Facades\Route;

require __DIR__.'/user.php';

require __DIR__.'/admin.php';

require __DIR__.'/auth.php';

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/produk', [HomeController::class, 'products'])->name('products');

Route::get('/produk/pencarian', [HomeController::class, 'products'])->name('products.search');

Route::get('/produk/detail/{slug}', function ($slug) {
    return app(HomeController::class)->productDetail(category: null, subcategory: null, slug: $slug);
})->name('products.detail.without.category.subcategory');

Route::get('/produk/detail/{category?}/{subcategory?}/{slug}', [HomeController::class, 'productDetail'])->name('products.detail');

Route::get('/produk/{category}', [HomeController::class, 'products'])->where('category', '^(?!pencarian|detail$)[a-zA-Z0-9-_]+$')->name('products.category');

Route::get('/produk/{category}/{subcategory}', [HomeController::class, 'products'])->name('products.subcategory');

Route::post('/api/xendit/webhook/paid', [XenditWebhookController::class, 'paid'])->middleware(['validate_xendit_webhook_token', 'throttle:10,1']);

Route::post('/api/xendit/webhook/refunded', [XenditWebhookController::class, 'refunded'])->middleware(['validate_xendit_webhook_token', 'throttle:10,1']);

Route::match(['get', 'put', 'patch', 'delete', 'options'], '/api/xendit/webhook/*', function () {
    abort(404);
});

Route::get('/faq', [HomeController::class, 'faq'])->name('faq');

Route::get('/bantuan', [HomeController::class, 'help'])->name('help');

Route::get('/syarat-dan-ketentuan', [HomeController::class, 'termsAndConditions'])->name('terms-and-conditions');

Route::get('/kebijakan-privasi', [HomeController::class, 'privacyPolicy'])->name('privacy-policy');

Route::get('/tentang-kami', [HomeController::class, 'about'])->name('about');

Route::get('/kontak-kami', [HomeController::class, 'contact'])->name('contact');
