<?php

use App\Http\Controllers\Admin\ChunkedUploadController;
use App\Http\Controllers\Admin\CollectionGroupCollectionController;
use App\Http\Controllers\Admin\CollectionGroupController;
use App\Http\Controllers\Admin\DeviceLocationController;
use App\Http\Controllers\Admin\DeviceMaintenanceController;
use App\Http\Controllers\Admin\FAQController;
use App\Http\Controllers\Admin\FeedbackController;
use App\Http\Controllers\Admin\FirebaseTestController;
use App\Http\Controllers\Admin\HealthReportController;
use App\Http\Controllers\Admin\KnowledgeController;
use App\Http\Controllers\Admin\MaintenanceBannerController;
use App\Http\Controllers\Admin\MarketplaceBannerController;
use App\Http\Controllers\Admin\MusicController;
use App\Http\Controllers\Admin\NewsController;
use App\Http\Controllers\Admin\S3UploadController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\ProductCollectionController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ProductIdentifierController;
use App\Http\Controllers\Admin\ProductInventoryController;
use App\Http\Controllers\Admin\ProductMediaController;
use App\Http\Controllers\Admin\ProductPricingController;
use App\Http\Controllers\Admin\ProductVariantController;
use App\Http\Controllers\Admin\SubscriptionController;
use App\Http\Controllers\Admin\MachineController;
use App\Http\Controllers\Admin\UserSubscriptionController;
use App\Http\Controllers\Admin\TherapyController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\UserLoginActivityController;
use App\Http\Controllers\Admin\WebSocketTestController;
use App\Http\Middleware\EnsureIsAdmin;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', EnsureIsAdmin::class])->as('admin.')->prefix('admin')->group(function () {
    // Chunked upload routes
    Route::post('/chunked-upload/initiate', [ChunkedUploadController::class, 'initiate'])->name('chunked-upload.initiate');
    Route::post('/chunked-upload/chunk', [ChunkedUploadController::class, 'uploadChunk'])->name('chunked-upload.chunk');
    Route::post('/chunked-upload/finalize', [ChunkedUploadController::class, 'finalize'])->name('chunked-upload.finalize');
    Route::post('/chunked-upload/cancel', [ChunkedUploadController::class, 'cancel'])->name('chunked-upload.cancel');

    Route::get('/news', [NewsController::class, 'index'])->name('news.index');
    Route::get('/news/create', [NewsController::class, 'create'])->name('news.create');
    Route::post('/news', [NewsController::class, 'store'])->name('news.store');
    Route::get('/news/{news}', [NewsController::class, 'show'])->name('news.show');
    Route::get('/news/{news}/edit', [NewsController::class, 'edit'])->name('news.edit');
    Route::put('/news/{news}', [NewsController::class, 'update'])->name('news.update');
    Route::delete('/news/{news}', [NewsController::class, 'destroy'])->name('news.destroy');

    Route::get('/products', [ProductController::class, 'index'])->name('products.index');
    Route::post('/products', [ProductController::class, 'store'])->name('products.store');
    Route::get('/products/{product}/edit', [ProductController::class, 'edit'])->name('products.edit');
    Route::put('/products/{product}', [ProductController::class, 'update'])->name('products.update');
    Route::put('/products/{product}/status', [ProductController::class, 'updateStatus'])->name('products.status.update');
    Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');

    Route::get('/products/{product}/media', [ProductMediaController::class, 'index'])->name('products.media.index');
    Route::post('/products/{product}/media', [ProductMediaController::class, 'store'])->name('products.media.store');
    Route::delete('/products/{product}/media/{media}', [ProductMediaController::class, 'destroy'])->name('products.media.destroy');
    Route::get('/products/{product}/media/reorder', [ProductMediaController::class, 'reorder'])->name('products.media.reorder');
    Route::post('/products/{product}/media/save-reorder', [ProductMediaController::class, 'saveReorder'])->name('products.media.save-reorder');

    Route::get('/products/{product}/variants', [ProductVariantController::class, 'index'])->name('products.variants.index');
    Route::get('/products/{product}/variants/configure', [ProductVariantController::class, 'configure'])->name('products.variants.configure');
    Route::post('/products/{product}/variants', [ProductVariantController::class, 'store'])->name('products.variants.store');
    Route::post('/products/{product}/variants/update-all', [ProductVariantController::class, 'updateAll'])->name('products.variants.update');
    Route::delete('/products/{product}/variants/{variant}', [ProductVariantController::class, 'destroy'])->name('products.variants.destroy');

    Route::get('/products/{product}/pricing', [ProductPricingController::class, 'index'])->name('products.pricing.index');
    Route::post('/products/{product}/pricing', [ProductPricingController::class, 'store'])->name('products.pricing.store');

    Route::get('/products/{product}/product-identifiers', [ProductIdentifierController::class, 'index'])->name('products.identifiers.index');
    Route::post('/products/{product}/product-identifiers', [ProductIdentifierController::class, 'store'])->name('products.identifiers.store');

    Route::get('/products/{product}/inventory', [ProductInventoryController::class, 'index'])->name('products.inventory.index');
    Route::post('/products/{product}/inventory', [ProductInventoryController::class, 'store'])->name('products.inventory.store');

    Route::get('/products/{product}/collections', [ProductCollectionController::class, 'index'])->name('products.collections.index');
    Route::post('/products/{product}/collections', [ProductCollectionController::class, 'store'])->name('products.collections.store');
    Route::delete('/products/{product}/collections/{collection}', [ProductCollectionController::class, 'destroy'])->name('products.collections.destroy');

    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show');
    Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');

    Route::get('/users/{user}/login-activities', [UserLoginActivityController::class, 'index'])->name('users.login-activities.index');

    Route::get('/collection-groups', [CollectionGroupController::class, 'index'])->name('collection-groups.index');
    Route::post('/collection-groups', [CollectionGroupController::class, 'store'])->name('collection-groups.store');
    Route::get('/collection-groups/{collectionGroup}/edit', [CollectionGroupController::class, 'edit'])->name('collection-groups.edit');
    Route::put('/collection-groups/{collectionGroup}', [CollectionGroupController::class, 'update'])->name('collection-groups.update');
    Route::delete('/collection-groups/{collectionGroup}', [CollectionGroupController::class, 'destroy'])->name('collection-groups.destroy');

    Route::post('/collection-groups/{collectionGroup}/collections', [CollectionGroupCollectionController::class, 'store'])->name('collection-groups.collections.store');
    Route::delete('/collection-groups/{collectionGroup}/collections/{collection}', [CollectionGroupCollectionController::class, 'destroy'])->name('collection-groups.collections.destroy');

    Route::get('/social-media', [\App\Http\Controllers\Admin\SocialMediaController::class, 'edit'])->name('social-media.edit');
    Route::put('/social-media', [\App\Http\Controllers\Admin\SocialMediaController::class, 'update'])->name('social-media.update');

    Route::get('/general-settings', [\App\Http\Controllers\Admin\GeneralSettingController::class, 'edit'])->name('general-settings.edit');
    Route::put('/general-settings', [\App\Http\Controllers\Admin\GeneralSettingController::class, 'update'])->name('general-settings.update');

    Route::get('/legal-settings', [\App\Http\Controllers\Admin\LegalSettingController::class, 'edit'])->name('legal-settings.edit');
    Route::put('/legal-settings', [\App\Http\Controllers\Admin\LegalSettingController::class, 'update'])->name('legal-settings.update');

    Route::get('/knowledge', [KnowledgeController::class, 'index'])->name('knowledge.index');
    Route::get('/knowledge/create', [KnowledgeController::class, 'create'])->name('knowledge.create');
    Route::post('/knowledge', [KnowledgeController::class, 'store'])->name('knowledge.store');
    Route::post('/knowledge/reorder', [KnowledgeController::class, 'reorder'])->name('knowledge.reorder');
    Route::get('/knowledge/{knowledge}', [KnowledgeController::class, 'show'])->name('knowledge.show');
    Route::get('/knowledge/{knowledge}/edit', [KnowledgeController::class, 'edit'])->name('knowledge.edit');
    Route::put('/knowledge/{knowledge}', [KnowledgeController::class, 'update'])->name('knowledge.update');
    Route::delete('/knowledge/{knowledge}', [KnowledgeController::class, 'destroy'])->name('knowledge.destroy');

    Route::get('/therapies', [TherapyController::class, 'index'])->name('therapies.index');
    Route::get('/therapies/create', [TherapyController::class, 'create'])->name('therapies.create');
    Route::post('/therapies', [TherapyController::class, 'store'])->name('therapies.store');
    Route::post('/therapies/reorder', [TherapyController::class, 'reorder'])->name('therapies.reorder');
    Route::get('/therapies/{therapy}/edit', [TherapyController::class, 'edit'])->name('therapies.edit');
    Route::put('/therapies/{therapy}', [TherapyController::class, 'update'])->name('therapies.update');
    Route::delete('/therapies/{therapy}', [TherapyController::class, 'destroy'])->name('therapies.destroy');

    Route::get('/faqs', [FAQController::class, 'index'])->name('faqs.index');
    Route::get('/faqs/create', [FAQController::class, 'create'])->name('faqs.create');
    Route::post('/faqs', [FAQController::class, 'store'])->name('faqs.store');
    Route::get('/faqs/{faq}', [FAQController::class, 'show'])->name('faqs.show');
    Route::get('/faqs/{faq}/edit', [FAQController::class, 'edit'])->name('faqs.edit');
    Route::put('/faqs/{faq}', [FAQController::class, 'update'])->name('faqs.update');
    Route::delete('/faqs/{faq}', [FAQController::class, 'destroy'])->name('faqs.destroy');

    Route::get('/feedbacks', [FeedbackController::class, 'index'])->name('feedbacks.index');
    Route::get('/feedbacks/{feedback}', [FeedbackController::class, 'show'])->name('feedbacks.show');

    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::put('/orders/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.status.update');

    // S3 direct upload
    Route::post('/s3-upload/presigned-url', [S3UploadController::class, 'presignedUrl'])->name('s3-upload.presigned-url');

    Route::get('/music', [MusicController::class, 'index'])->name('music.index');
    Route::get('/music/create', [MusicController::class, 'create'])->name('music.create');
    Route::post('/music', [MusicController::class, 'store'])->name('music.store');
    Route::get('/music/{music}/edit', [MusicController::class, 'edit'])->name('music.edit');
    Route::put('/music/{music}', [MusicController::class, 'update'])->name('music.update');
    Route::delete('/music/{music}', [MusicController::class, 'destroy'])->name('music.destroy');

    Route::get('/websocket-test', [WebSocketTestController::class, 'index'])->name('websocket-test.index');
    Route::post('/websocket-test/trigger', [WebSocketTestController::class, 'trigger'])->name('websocket-test.trigger');

    Route::get('/firebase-test', [FirebaseTestController::class, 'index'])->name('firebase-test.index');
    Route::post('/firebase-test/send', [FirebaseTestController::class, 'send'])->name('firebase-test.send');
    Route::post('/firebase-test/test-token', [FirebaseTestController::class, 'testToken'])->name('firebase-test.test-token');

    Route::get('/device-maintenances', [DeviceMaintenanceController::class, 'index'])->name('device-maintenances.index');
    Route::get('/device-maintenances/{deviceMaintenance}', [DeviceMaintenanceController::class, 'show'])->name('device-maintenances.show');
    Route::put('/device-maintenances/{deviceMaintenance}/status', [DeviceMaintenanceController::class, 'updateStatus'])->name('device-maintenances.status.update');

    Route::get('/maintenance-banners', [MaintenanceBannerController::class, 'index'])->name('maintenance-banners.index');
    Route::get('/maintenance-banners/create', [MaintenanceBannerController::class, 'create'])->name('maintenance-banners.create');
    Route::post('/maintenance-banners', [MaintenanceBannerController::class, 'store'])->name('maintenance-banners.store');
    Route::get('/maintenance-banners/{maintenanceBanner}', [MaintenanceBannerController::class, 'show'])->name('maintenance-banners.show');
    Route::get('/maintenance-banners/{maintenanceBanner}/edit', [MaintenanceBannerController::class, 'edit'])->name('maintenance-banners.edit');
    Route::put('/maintenance-banners/{maintenanceBanner}', [MaintenanceBannerController::class, 'update'])->name('maintenance-banners.update');
    Route::delete('/maintenance-banners/{maintenanceBanner}', [MaintenanceBannerController::class, 'destroy'])->name('maintenance-banners.destroy');

    Route::get('/marketplace-banners', [MarketplaceBannerController::class, 'index'])->name('marketplace-banners.index');
    Route::get('/marketplace-banners/create', [MarketplaceBannerController::class, 'create'])->name('marketplace-banners.create');
    Route::post('/marketplace-banners', [MarketplaceBannerController::class, 'store'])->name('marketplace-banners.store');
    Route::get('/marketplace-banners/{marketplaceBanner}', [MarketplaceBannerController::class, 'show'])->name('marketplace-banners.show');
    Route::get('/marketplace-banners/{marketplaceBanner}/edit', [MarketplaceBannerController::class, 'edit'])->name('marketplace-banners.edit');
    Route::put('/marketplace-banners/{marketplaceBanner}', [MarketplaceBannerController::class, 'update'])->name('marketplace-banners.update');
    Route::delete('/marketplace-banners/{marketplaceBanner}', [MarketplaceBannerController::class, 'destroy'])->name('marketplace-banners.destroy');

    Route::get('/device-locations', [DeviceLocationController::class, 'index'])->name('device-locations.index');
    Route::get('/device-locations/{userDevice}', [DeviceLocationController::class, 'show'])->name('device-locations.show');

    Route::get('/health-reports', [HealthReportController::class, 'index'])->name('health-reports.index');
    Route::get('/health-reports/create', [HealthReportController::class, 'create'])->name('health-reports.create');
    Route::post('/health-reports', [HealthReportController::class, 'store'])->name('health-reports.store');
    Route::get('/health-reports/{healthReport}/{type}', [HealthReportController::class, 'show'])->name('health-reports.show');
    Route::delete('/health-reports/{healthReport}', [HealthReportController::class, 'destroy'])->name('health-reports.destroy');

    Route::get('/subscriptions', [SubscriptionController::class, 'index'])->name('subscription.index');
    Route::get('/subscriptions/create', [SubscriptionController::class, 'create'])->name('subscription.create');
    Route::post('/subscriptions', [SubscriptionController::class, 'store'])->name('subscription.store');
    Route::get('/subscriptions/{subscription}/edit', [SubscriptionController::class, 'edit'])->name('subscription.edit');
    Route::put('/subscriptions/{subscription}', [SubscriptionController::class, 'update'])->name('subscription.update');
    Route::delete('/subscriptions/{subscription}', [SubscriptionController::class, 'destroy'])->name('subscription.destroy');

    // User subscriptions
    Route::get('/user-subscriptions', [UserSubscriptionController::class, 'index'])->name('user-subscriptions.index');
    Route::get('/user-subscriptions/{userSubscription}', [UserSubscriptionController::class, 'show'])->name('user-subscriptions.show');
    Route::post('/user-subscriptions/{userSubscription}/cancel', [UserSubscriptionController::class, 'cancel'])->name('user-subscriptions.cancel');
    Route::post('/user-subscriptions/{userSubscription}/extend', [UserSubscriptionController::class, 'extend'])->name('user-subscriptions.extend');

    // Machines
    Route::get('/machines', [MachineController::class, 'index'])->name('machines.index');
    Route::get('/machines/create', [MachineController::class, 'create'])->name('machines.create');
    Route::post('/machines', [MachineController::class, 'store'])->name('machines.store');
    Route::get('/machines/{machine}', [MachineController::class, 'show'])->name('machines.show');
    Route::get('/machines/{machine}/edit', [MachineController::class, 'edit'])->name('machines.edit');
    Route::put('/machines/{machine}', [MachineController::class, 'update'])->name('machines.update');
    Route::delete('/machines/{machine}', [MachineController::class, 'destroy'])->name('machines.destroy');
    Route::post('/machines/{machine}/unbind', [MachineController::class, 'unbind'])->name('machines.unbind');
    Route::post('/machines/{machine}/activate', [MachineController::class, 'activate'])->name('machines.activate');
    Route::post('/machines/{machine}/deactivate', [MachineController::class, 'deactivate'])->name('machines.deactivate');
});
