<?php

use App\Http\Controllers\Settings\AddressController;
use App\Http\Controllers\Settings\PasswordController;
use App\Http\Controllers\Settings\ProfileController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware('auth')->group(function () {
    Route::redirect('settings', '/settings/profile');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('settings/password', [PasswordController::class, 'edit'])->name('password.edit');

    Route::put('settings/password', [PasswordController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('password.update');

    Route::get('settings/appearance', function () {
        return Inertia::render('settings/appearance');
    })->name('appearance.edit');

    Route::get('settings/address', [AddressController::class, 'index'])->name('address.index');
    Route::post('settings/address', [AddressController::class, 'store'])->name('address.store');
    Route::get('settings/address/{address}/edit', [AddressController::class, 'edit'])->name('address.edit');
    Route::put('settings/address/{address}', [AddressController::class, 'update'])->name('address.update');
    Route::delete('settings/address/{address}', [AddressController::class, 'destroy'])->name('address.destroy');
});
