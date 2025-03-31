<?php

use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::middleware('guest')->group(function () {
    Volt::route('daftar', 'pages.auth.register')->name('register');

    Volt::route('masuk', 'pages.auth.login')->name('login');

    Volt::route('lupa-password', 'pages.auth.forgot-password')->name('password.request');

    Volt::route('reset-password/{token}', 'pages.auth.reset-password')->name('password.reset');
});

Route::middleware('auth')->group(function () {
    Volt::route('verifikasi-email', 'pages.auth.verify-email')->name('verification.notice');

    Route::get('verifikasi-email/{id}/{hash}', VerifyEmailController::class)->middleware(['signed', 'throttle:6,1'])->name('verification.verify');

    Volt::route('konfirmasi-password', 'pages.auth.confirm-password')->name('password.confirm');
});
