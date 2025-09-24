<?php

use App\Http\Controllers\DeviceController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\OrderHistoryController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::redirect('/', '/login')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');

    Route::get('devices', [DeviceController::class, 'index'])->name('devices.index');

    Route::get('order-history', [OrderHistoryController::class, 'index'])->name('order-history.index');

    Route::get('news', [NewsController::class, 'index'])->name('news.index');
    Route::get('news/{news}', [NewsController::class, 'show'])->name('news.show');
});

require __DIR__.'/admin.php';
require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
