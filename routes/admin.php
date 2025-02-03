<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DiscountController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\RefundController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SubcategoryController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'admin_page_access'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::prefix('manajemen-pesanan')->name('orders.')->group(function () {
        Route::get('/', [OrderController::class, 'index'])->name('index');
        Route::get('/{orderNumber}/detail', [OrderController::class, 'show'])->name('show');
    });

    Route::prefix('manajemen-refund')->name('refunds.')->group(function () {
        Route::get('/', [RefundController::class, 'index'])->name('index');
        Route::get('/{id}/detail', [RefundController::class, 'show'])->name('show');
    });

    Route::prefix('manajemen-produk')->name('products.')->group(function () {
        Route::get('/', [ProductController::class, 'index'])->name('index');
        Route::get('/tambah', [ProductController::class, 'create'])->name('create');
        Route::get('/{slug}/detail', [ProductController::class, 'show'])->name('show');
        Route::get('/{slug}/ubah', [ProductController::class, 'edit'])->name('edit');
    });

    Route::prefix('manajemen-arsip-produk')->name('archived-products.')->group(function () {
        Route::get('/', [ProductController::class, 'archive'])->name('index');
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
        Route::get('/{code}/detail', [DiscountController::class, 'show'])->name('show');
        Route::get('/{code}/ubah', [DiscountController::class, 'edit'])->name('edit');
    });

    Route::prefix('manajemen-pelanggan')->name('users.')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::get('/{id}/detail', [UserController::class, 'show'])->name('show');
    });

    Route::prefix('manajemen-admin')->name('admins.')->group(function () {
        Route::get('/', [AdminController::class, 'index'])->name('index');
        Route::get('/tambah', [AdminController::class, 'create'])->name('create');
        Route::get('/{id}/detail', [AdminController::class, 'show'])->name('show');
        Route::get('/{id}/ubah', [AdminController::class, 'edit'])->name('edit');
    });

    Route::name('reports.')->group(function () {
        Route::get('/laporan-penjualan', [ReportController::class, 'sales'])->name('sales');
    });

    Route::get('/profil-saya', [ProfileController::class, 'index'])->name('profile');

    Route::get('/pengaturan-akun', [ProfileController::class, 'setting'])->name('setting');
});
