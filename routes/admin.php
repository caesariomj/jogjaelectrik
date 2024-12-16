<?php

use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DiscountController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\SubcategoryController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:admin|super_admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::prefix('manajemen-pesanan')->name('orders.')->group(function () {
        Route::get('/', [OrderController::class, 'index'])->name('index');
        Route::get('/{orderNumber}/detail', [OrderController::class, 'show'])->name('show');
    });

    Route::prefix('manajemen-produk')->name('products.')->group(function () {
        Route::get('/', [ProductController::class, 'index'])->name('index');
        Route::get('/tambah', [ProductController::class, 'create'])->name('create');
        Route::post('/', [ProductController::class, 'store'])->name('store');
        Route::get('/{slug}/detail', [ProductController::class, 'show'])->name('show');
        Route::get('/{slug}/ubah', [ProductController::class, 'edit'])->name('edit');
        Route::patch('/{product}', [ProductController::class, 'update'])->name('update');
        Route::delete('/{product}', [ProductController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('manajemen-gambar-produk')->name('product-images.')->group(function () {
        Route::delete('/{image}', [ProductController::class, 'destroyImage'])->name('destroy');
    });

    Route::prefix('manajemen-arsip-produk')->name('archived-products.')->group(function () {
        Route::get('/', [ProductController::class, 'archive'])->name('index');
        Route::patch('/{id}', [ProductController::class, 'restore'])->name('restore');
        Route::delete('/{id}', [ProductController::class, 'forceDelete'])->name('forceDelete');
    });

    Route::prefix('manajemen-kategori')->name('categories.')->group(function () {
        Route::get('/', [CategoryController::class, 'index'])->name('index');
        Route::get('/tambah', [CategoryController::class, 'create'])->name('create');
        Route::get('/{slug}/detail', [CategoryController::class, 'show'])->name('show');
        Route::get('/{slug}/ubah', [CategoryController::class, 'edit'])->name('edit');
    });

    Route::prefix('manajemen-subkategori')->name('subcategories.')->group(function () {
        Route::get('/', [SubcategoryController::class, 'index'])->name('index');
        Route::get('/tambah', [SubcategoryController::class, 'create'])->name('create');
        Route::get('/{slug}/detail', [SubcategoryController::class, 'show'])->name('show');
        Route::get('/{slug}/ubah', [SubcategoryController::class, 'edit'])->name('edit');
    });

    Route::prefix('manajemen-diskon')->name('discounts.')->group(function () {
        Route::get('/', [DiscountController::class, 'index'])->name('index');
        Route::get('/tambah', [DiscountController::class, 'create'])->name('create');
        Route::post('/', [DiscountController::class, 'store'])->name('store');
        Route::get('/{code}/detail', [DiscountController::class, 'show'])->name('show');
        Route::get('/{code}/ubah', [DiscountController::class, 'edit'])->name('edit');
        Route::patch('/{discount}/reset-penggunaan-diskon', [DiscountController::class, 'resetUsage'])->name('resetUsage');
        Route::patch('/{discount}', [DiscountController::class, 'update'])->name('update');
        Route::delete('/{discount}', [DiscountController::class, 'destroy'])->name('destroy');
    });
});
