<?php

use App\Http\Controllers\DeviceController;
use App\Http\Controllers\HealthReportController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\OrderHistoryController;
use App\Http\Controllers\UsageHistoryController;
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

    Route::get('health-reports', [HealthReportController::class, 'index'])->name('health-reports.index');
    Route::get('health-reports/create', [HealthReportController::class, 'create'])->name('health-reports.create');
    Route::post('health-reports', [HealthReportController::class, 'store'])->name('health-reports.store');
    Route::get('health-reports/{healthReport}', [HealthReportController::class, 'show'])->name('health-reports.show');

    Route::get('usage-history', [UsageHistoryController::class, 'index'])->name('usage-history.index');
    Route::get('usage-history/{usageHistory}', [UsageHistoryController::class, 'show'])->name('usage-history.show');
});

require __DIR__.'/admin.php';
require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
