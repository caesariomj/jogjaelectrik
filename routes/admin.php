<?php

use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\SubcategoryController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:admin|super_admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::prefix('manajemen-kategori')->name('categories.')->group(function () {
        Route::get('/', [CategoryController::class, 'index'])->name('index');
        Route::get('/tambah', [CategoryController::class, 'create'])->name('create');
        Route::post('/', [CategoryController::class, 'store'])->name('store');
        Route::get('/{slug}/detail', [CategoryController::class, 'show'])->name('show');
        Route::get('/{slug}/ubah', [CategoryController::class, 'edit'])->name('edit');
        Route::patch('/{category}', [CategoryController::class, 'update'])->name('update');
        Route::delete('/{category}', [CategoryController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('manajemen-subkategori')->name('subcategories.')->group(function () {
        Route::get('/', [SubcategoryController::class, 'index'])->name('index');
        Route::get('/tambah', [SubcategoryController::class, 'create'])->name('create');
        Route::post('/', [SubcategoryController::class, 'store'])->name('store');
        Route::get('/{slug}/detail', [SubcategoryController::class, 'show'])->name('show');
        Route::get('/{slug}/ubah', [SubcategoryController::class, 'edit'])->name('edit');
        Route::patch('/{subcategory}', [SubcategoryController::class, 'update'])->name('update');
        Route::delete('/{subcategory}', [SubcategoryController::class, 'destroy'])->name('destroy');
    });
});
