<?php

use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\CustomTherapyController;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\DeviceMaintenanceController;
use App\Http\Controllers\HealthReportController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\OrderHistoryController;
use App\Http\Controllers\Payment\RevpayCallbackController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\UsageHistoryController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::redirect('/', '/login')->name('home');

// RevPay payment callbacks (no auth middleware)
Route::post('/payment/revpay/callback', [RevpayCallbackController::class, 'backendCallback'])->name('payment.revpay.callback');
Route::get('/payment/revpay/return', [RevpayCallbackController::class, 'returnUrl'])->name('payment.revpay.return');

// Public product routes
Route::get('products', [ProductController::class, 'index'])->name('products.index');
Route::get('products/{product}', [ProductController::class, 'show'])->name('products.show');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');

    Route::get('devices', [DeviceController::class, 'index'])->name('devices.index');
    Route::get('devices/{device}', [DeviceController::class, 'show'])->name('devices.show');

    // Cart routes
    Route::get('cart', [CartController::class, 'index'])->name('cart.index');
    Route::post('cart/add', [CartController::class, 'add'])->name('cart.add');
    Route::put('cart/lines/{cartLine}', [CartController::class, 'updateLine'])->name('cart.lines.update');
    Route::delete('cart/lines/{cartLine}', [CartController::class, 'removeLine'])->name('cart.lines.destroy');

    // Checkout routes
    Route::get('checkout', [CheckoutController::class, 'index'])->name('checkout.index');
    Route::post('checkout/address', [CheckoutController::class, 'saveAddress'])->name('checkout.address');
    Route::get('checkout/review', [CheckoutController::class, 'review'])->name('checkout.review');
    Route::post('checkout/complete', [CheckoutController::class, 'complete'])->name('checkout.complete');
    Route::get('checkout/success/{order}', [CheckoutController::class, 'success'])->name('checkout.success');

    // Order history routes
    Route::get('order-history', [OrderHistoryController::class, 'index'])->name('order-history.index');
    Route::get('order-history/{order}', [OrderHistoryController::class, 'show'])->name('order-history.show');

    Route::get('news', [NewsController::class, 'index'])->name('news.index');
    Route::get('news/{news}', [NewsController::class, 'show'])->name('news.show');

    Route::get('health-reports', [HealthReportController::class, 'index'])->name('health-reports.index');
    Route::get('health-reports/{healthReport}', [HealthReportController::class, 'show'])->name('health-reports.show');

    Route::get('usage-history', [UsageHistoryController::class, 'index'])->name('usage-history.index');
    Route::get('usage-history/{usageHistory}', [UsageHistoryController::class, 'show'])->name('usage-history.show');

    Route::get('device-maintenance', [DeviceMaintenanceController::class, 'index'])->name('device-maintenance.index');
    Route::get('device-maintenance/create', [DeviceMaintenanceController::class, 'create'])->name('device-maintenance.create');
    Route::post('device-maintenance', [DeviceMaintenanceController::class, 'store'])->name('device-maintenance.store');
    Route::get('device-maintenance/{deviceMaintenance}', [DeviceMaintenanceController::class, 'show'])->name('device-maintenance.show');
    Route::post('device-maintenance/{deviceMaintenance}/approve', [DeviceMaintenanceController::class, 'approve'])->name('device-maintenance.approve');
    Route::get('device-maintenance/{deviceMaintenance}/edit', [DeviceMaintenanceController::class, 'edit'])->name('device-maintenance.edit');
    Route::put('device-maintenance/{deviceMaintenance}', [DeviceMaintenanceController::class, 'update'])->name('device-maintenance.update');

    Route::get('custom-therapies', [CustomTherapyController::class, 'index'])->name('custom-therapies.index');
    Route::get('custom-therapies/{customTherapy}/edit', [CustomTherapyController::class, 'edit'])->name('custom-therapies.edit');
    Route::put('custom-therapies/{customTherapy}', [CustomTherapyController::class, 'update'])->name('custom-therapies.update');
    Route::delete('custom-therapies/{customTherapy}', [CustomTherapyController::class, 'destroy'])->name('custom-therapies.destroy');
});

require __DIR__.'/admin.php';
require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
