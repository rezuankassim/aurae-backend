<?php

use App\Http\Controllers\Api\AuthenticationController;
use App\Http\Controllers\Api\DeviceController;
use App\Http\Controllers\Api\FaqController;
use App\Http\Middleware\EnsureDevice;
use App\Http\Resources\BaseResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::group(['middleware' => [EnsureDevice::class]], function () {
    Route::group(['middleware' => ['auth:sanctum']], function () {
        Route::get('/user', function (Request $request) {
            return BaseResource::make($request->user())
                ->additional([
                    'status' => 200,
                    'message' => 'User retrieved successfully.',
                ]);
        });

        Route::post('/logout', [AuthenticationController::class, 'logout'])->name('api.logout');

        Route::get('/faqs', [FaqController::class, 'index'])->name('api.faqs.index');
        Route::get('/faqs/{faq}', [FaqController::class, 'show'])->name('api.faqs.show');

        Route::post('/device-login', [DeviceController::class, 'login'])->name('api.device.login');
    });

    Route::post('/login', [AuthenticationController::class, 'login'])->name('api.login');
    Route::post('/register', [AuthenticationController::class, 'register'])->name('api.register');
    Route::post('/send-verify', [AuthenticationController::class, 'sendVerify'])->name('api.send_verify');
    Route::post('/verify-phone', [AuthenticationController::class, 'verifyPhone'])->name('api.verify_phone');

    Route::get('/device-retrieve', [DeviceController::class, 'retrieve'])->name('api.device.retrieve');
});