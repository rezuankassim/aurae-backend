<?php

use App\Http\Controllers\Api\AuthenticationController;
use App\Http\Middleware\EnsureDevice;
use App\Http\Resources\BaseResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::group(['middleware' => [EnsureDevice::class]], function () {
    Route::group(['middleware' => ['auth:sanctum']], function () {
        Route::get('/user', function (Request $request) {
            return BaseResource::make($request->user());
        });
    });

    Route::post('/login', [AuthenticationController::class, 'login'])->name('api.login');
});