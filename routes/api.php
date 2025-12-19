<?php

use App\Http\Controllers\Api\AuthenticationController;
use App\Http\Controllers\Api\CustomTherapyController;
use App\Http\Controllers\Api\DeviceController;
use App\Http\Controllers\Api\DeviceGuestController;
use App\Http\Controllers\Api\DeviceTokenController;
use App\Http\Controllers\Api\FaqController;
use App\Http\Controllers\Api\FeedbackController;
use App\Http\Controllers\Api\GeneralSettingController;
use App\Http\Controllers\Api\KnowledgeController;
use App\Http\Controllers\Api\MusicController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\TherapyController;
use App\Http\Controllers\Api\UsageHistoryController;
use App\Http\Controllers\Api\VideoStreamController;
use App\Http\Middleware\EnsureDevice;
use App\Http\Resources\BaseResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Unprotected routes
Route::get('/general-settings', [GeneralSettingController::class, 'index'])->name('api.general-settings.index');

Route::group(['middleware' => [EnsureDevice::class]], function () {
    Route::group(['middleware' => ['auth:sanctum']], function () {
        Route::get('/user', function (Request $request) {
            $user = $request->user();
            $user->load('guest');

            return BaseResource::make($user)
                ->additional([
                    'status' => 200,
                    'message' => 'User retrieved successfully.',
                    'is_guest' => $user->isGuest(),
                ]);
        });

        Route::post('/logout', [AuthenticationController::class, 'logout'])->name('api.logout');

        Route::get('/knowledge', [KnowledgeController::class, 'index'])->name('api.knowledge.index');
        Route::get('/knowledge/{knowledge}', [KnowledgeController::class, 'show'])->name('api.knowledge.show');
        Route::get('/knowledge/{knowledge}/video', [VideoStreamController::class, 'streamKnowledgeVideo'])->name('api.knowledge.video.stream');

        Route::get('/therapies', [TherapyController::class, 'index'])->name('api.therapies.index');

        Route::get('/music', [MusicController::class, 'index'])->name('api.music.index');

        Route::get('/custom-therapies', [CustomTherapyController::class, 'index'])->name('api.custom-therapies.index');
        Route::post('/custom-therapies', [CustomTherapyController::class, 'store'])->name('api.custom-therapies.store');

        Route::post('/usage-histories', [UsageHistoryController::class, 'store'])->name('api.usage-histories.store');

        Route::get('/notifications', [NotificationController::class, 'index'])->name('api.notifications.index');

        Route::post('/device/fcm-token', [DeviceTokenController::class, 'update'])->name('api.device.fcm-token.update');

        Route::post('/device-login', [DeviceController::class, 'login'])->name('api.device.login');
    });

    Route::get('/faqs', [FaqController::class, 'index'])->name('api.faqs.index');
    Route::get('/faqs/{faq}', [FaqController::class, 'show'])->name('api.faqs.show');

    Route::post('/feedback', [FeedbackController::class, 'store'])->name('api.feedback.store');

    Route::post('/login', [AuthenticationController::class, 'login'])->name('api.login');
    Route::post('/register', [AuthenticationController::class, 'register'])->name('api.register');
    Route::post('/send-verify', [AuthenticationController::class, 'sendVerify'])->name('api.send_verify');
    Route::post('/verify-phone', [AuthenticationController::class, 'verifyPhone'])->name('api.verify_phone');

    Route::post('/device-retrieve', [DeviceController::class, 'retrieve'])->name('api.device.retrieve');

    // Guest management routes
    Route::get('/device-guests', [DeviceGuestController::class, 'index'])->name('api.device.guests.index');
    Route::post('/device-guest-create', [DeviceGuestController::class, 'store'])->name('api.device.guests.store');
    Route::post('/device-guest-login', [DeviceGuestController::class, 'login'])->name('api.device.guests.login');
});
