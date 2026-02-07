<?php

use App\Http\Controllers\Api\AddressController;
use App\Http\Controllers\Api\ApkController;
use App\Http\Controllers\Api\AuthenticationController;
use App\Http\Controllers\Api\CheckoutController;
use App\Http\Controllers\Api\CustomTherapyController;
use App\Http\Controllers\Api\DeviceController;
use App\Http\Controllers\Api\DeviceGuestController;
use App\Http\Controllers\Api\DeviceMaintenanceController;
use App\Http\Controllers\Api\DeviceTokenController;
use App\Http\Controllers\Api\EcommerceController;
use App\Http\Controllers\Api\FaqController;
use App\Http\Controllers\Api\FeedbackController;
use App\Http\Controllers\Api\GeneralSettingController;
use App\Http\Controllers\Api\LegalController;
use App\Http\Controllers\Api\HealthReportController;
use App\Http\Controllers\Api\KnowledgeController;
use App\Http\Controllers\Api\MaintenanceBannerController;
use App\Http\Controllers\Api\MarketplaceBannerController;
use App\Http\Controllers\Api\MusicController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\NewsController;
use App\Http\Controllers\Api\PaymentHistoryController;
use App\Http\Controllers\Api\UserSettingController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\SubscriptionController;
use App\Http\Controllers\Api\TherapyController;
use App\Http\Controllers\Api\UsageHistoryController;
use App\Http\Controllers\Api\VideoStreamController;
use App\Http\Controllers\Api\WebSocketController;
use App\Http\Middleware\EnsureDevice;
use App\Http\Resources\BaseResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::group(['middleware' => [EnsureDevice::class, 'check.app.version']], function () {
    // Mobile APK routes
    Route::get('/apk/info', [ApkController::class, 'info'])->name('api.apk.info');
    Route::get('/apk/download', [ApkController::class, 'download'])->name('api.apk.download');

    // Tablet APK routes
    Route::get('/apk/tablet/info', [ApkController::class, 'tabletInfo'])->name('api.apk.tablet.info');
    Route::get('/apk/tablet/download', [ApkController::class, 'tabletDownload'])->name('api.apk.tablet.download');

    // Unprotected routes
    Route::get('/general-settings', [GeneralSettingController::class, 'index'])->name('api.general-settings.index');
    Route::get('/countries', [AddressController::class, 'countries'])->name('api.countries.index');
    Route::get('/states', [AddressController::class, 'states'])->name('api.states.index');

    Route::get('/faqs', [FaqController::class, 'index'])->name('api.faqs.index');
    Route::get('/faqs/{faq}', [FaqController::class, 'show'])->name('api.faqs.show');

    Route::get('/terms-and-conditions', [LegalController::class, 'termsAndConditions'])->name('api.legal.terms');
    Route::get('/privacy-policy', [LegalController::class, 'privacyPolicy'])->name('api.legal.privacy');

    Route::post('/feedback', [FeedbackController::class, 'store'])->name('api.feedback.store');

    Route::post('/login', [AuthenticationController::class, 'login'])->name('api.login');
    Route::post('/register', [AuthenticationController::class, 'register'])->name('api.register');
    Route::post('/send-verify', [AuthenticationController::class, 'sendVerify'])->name('api.send_verify');
    Route::post('/verify-phone', [AuthenticationController::class, 'verifyPhone'])->name('api.verify_phone');
    Route::post('/forgot-password', [AuthenticationController::class, 'forgotPassword'])->name('api.forgot_password');
    Route::post('/verify-reset-otp', [AuthenticationController::class, 'verifyResetOtp'])->name('api.verify_reset_otp');
    Route::post('/reset-password', [AuthenticationController::class, 'resetPassword'])->name('api.reset_password');

    Route::post('/device-retrieve', [DeviceController::class, 'retrieve'])->name('api.device.retrieve');

    // WebSocket ping-pong
    Route::post('/ws/ping', [WebSocketController::class, 'ping'])->name('api.ws.ping');

    // Guest management routes
    Route::get('/device-guests', [DeviceGuestController::class, 'index'])->name('api.device.guests.index');
    Route::post('/device-guest-create', [DeviceGuestController::class, 'store'])->name('api.device.guests.store');
    Route::post('/device-guest-login', [DeviceGuestController::class, 'login'])->name('api.device.guests.login');
    Route::delete('/device-guests/{guestId}', [DeviceGuestController::class, 'destroy'])->name('api.device.guests.destroy');

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

        Route::get('/news', [NewsController::class, 'index'])->name('api.news.index');
        Route::get('/news/{news}', [NewsController::class, 'show'])->name('api.news.show');

        Route::get('/therapies', [TherapyController::class, 'index'])->name('api.therapies.index');

        Route::get('/music', [MusicController::class, 'index'])->name('api.music.index');

        Route::get('/custom-therapies', [CustomTherapyController::class, 'index'])->name('api.custom-therapies.index');
        Route::post('/custom-therapies', [CustomTherapyController::class, 'store'])->name('api.custom-therapies.store');
        Route::delete('/custom-therapies/{customTherapy}', [CustomTherapyController::class, 'destroy'])->name('api.custom-therapies.destroy');

        Route::post('/usage-histories', [UsageHistoryController::class, 'store'])->name('api.usage-histories.store');
        Route::get('/usage-histories/chart', [UsageHistoryController::class, 'chart'])->name('api.usage-histories.chart');

        Route::get('/notifications', [NotificationController::class, 'index'])->name('api.notifications.index');

        Route::post('/device/fcm-token', [DeviceTokenController::class, 'update'])->name('api.device.fcm-token.update');

        Route::post('/device-login', [DeviceController::class, 'login'])->name('api.device.login');

        Route::get('/collections', [EcommerceController::class, 'collections'])->name('api.ecommerce.collections');
        Route::get('/cart', [EcommerceController::class, 'cart'])->name('api.ecommerce.cart');
        Route::post('/cart/add', [EcommerceController::class, 'addToCart'])->name('api.ecommerce.cart.add');
        Route::post('/cart/remove', [EcommerceController::class, 'removeFromCart'])->name('api.ecommerce.cart.remove');

        // Checkout and payment routes
        Route::post('/checkout/set-addresses', [CheckoutController::class, 'setAddresses'])->name('api.checkout.set-addresses');
        Route::post('/checkout/initiate-payment', [CheckoutController::class, 'initiatePayment'])->name('api.checkout.initiate-payment');
        Route::get('/checkout/payment-status/{reference}', [CheckoutController::class, 'checkPaymentStatus'])->name('api.checkout.payment-status');
        Route::get('/orders', [CheckoutController::class, 'orderHistory'])->name('api.orders.index');
        Route::get('/orders/{order}', [CheckoutController::class, 'orderDetail'])->name('api.orders.show');

        Route::get('/payment-history', [PaymentHistoryController::class, 'index'])->name('api.payment-history.index');

        Route::get('/addresses', [AddressController::class, 'index'])->name('api.addresses.index');
        Route::post('/addresses', [AddressController::class, 'store'])->name('api.addresses.store');
        Route::get('/addresses/{address}', [AddressController::class, 'show'])->name('api.addresses.show');
        Route::post('/addresses/{address}', [AddressController::class, 'update'])->name('api.addresses.update');
        Route::delete('/addresses/{address}', [AddressController::class, 'destroy'])->name('api.addresses.destroy');

        Route::get('/devices', [DeviceMaintenanceController::class, 'devices'])->name('api.devices.index');
        Route::get('/device-maintenances/availability', [DeviceMaintenanceController::class, 'availability'])->name('api.device-maintenances.availability');
        Route::get('/device-maintenances', [DeviceMaintenanceController::class, 'index'])->name('api.device-maintenances.index');
        Route::post('/device-maintenances', [DeviceMaintenanceController::class, 'store'])->name('api.device-maintenances.store');
        Route::get('/device-maintenances/{deviceMaintenance}', [DeviceMaintenanceController::class, 'show'])->name('api.device-maintenances.show');
        Route::delete('/device-maintenances/{deviceMaintenance}/cancel', [DeviceMaintenanceController::class, 'cancel'])->name('api.device-maintenances.cancel');

        Route::get('/maintenance-banners', [MaintenanceBannerController::class, 'index'])->name('api.maintenance-banners.index');
        Route::get('/marketplace-banners', [MarketplaceBannerController::class, 'index'])->name('api.marketplace-banners.index');

        Route::get('/health-reports', [HealthReportController::class, 'index'])->name('api.health-reports.index');
        Route::get('/health-reports/{healthReport}', [HealthReportController::class, 'show'])->name('api.health-reports.show');
        Route::get('/health-reports/{healthReport}/file/{type}', [HealthReportController::class, 'file'])->name('api.health-reports.file');

        // Subscription routes
        Route::get('/subscriptions', [SubscriptionController::class, 'index'])->name('api.subscriptions.index');
        Route::get('/user/subscription', [SubscriptionController::class, 'userSubscription'])->name('api.user.subscription');

        // Profile routes
        Route::get('/profile', [ProfileController::class, 'show'])->name('api.profile.show');
        Route::post('/profile', [ProfileController::class, 'update'])->name('api.profile.update');
        Route::post('/profile/verify-phone', [ProfileController::class, 'verifyPhoneChange'])->name('api.profile.verify-phone');
        Route::post('/profile/resend-otp', [ProfileController::class, 'resendPhoneVerificationOtp'])->name('api.profile.resend-otp');

        // User settings routes
        Route::get('/settings', [UserSettingController::class, 'show'])->name('api.settings.show');
        Route::post('/settings', [UserSettingController::class, 'update'])->name('api.settings.update');
    });
});
