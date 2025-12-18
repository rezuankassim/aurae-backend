<?php

use App\Http\Controllers\Admin\ChunkedUploadController;
use App\Http\Controllers\Admin\CollectionGroupCollectionController;
use App\Http\Controllers\Admin\CollectionGroupController;
use App\Http\Controllers\Admin\FAQController;
use App\Http\Controllers\Admin\FeedbackController;
use App\Http\Controllers\Admin\KnowledgeController;
use App\Http\Controllers\Admin\MusicController;
use App\Http\Controllers\Admin\NewsController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\ProductCollectionController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ProductIdentifierController;
use App\Http\Controllers\Admin\ProductInventoryController;
use App\Http\Controllers\Admin\ProductMediaController;
use App\Http\Controllers\Admin\ProductPricingController;
use App\Http\Controllers\Admin\ProductVariantController;
use App\Http\Controllers\Admin\TherapyController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\UserLoginActivityController;
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

    Route::get('/products/{product}/media', [ProductMediaController::class, 'index'])->name('products.media.index');
    Route::post('/products/{product}/media', [ProductMediaController::class, 'store'])->name('products.media.store');
    Route::delete('/products/{product}/media/{media}', [ProductMediaController::class, 'destroy'])->name('products.media.destroy');
    Route::get('/products/{product}/media/reorder', [ProductMediaController::class, 'reorder'])->name('products.media.reorder');
    Route::post('/products/{product}/media/save-reorder', [ProductMediaController::class, 'saveReorder'])->name('products.media.save-reorder');

    Route::get('/products/{product}/variants', [ProductVariantController::class, 'index'])->name('products.variants.index');
    Route::get('/products/{product}/variants/configure', [ProductVariantController::class, 'configure'])->name('products.variants.configure');
    Route::post('/products/{product}/variants', [ProductVariantController::class, 'store'])->name('products.variants.store');
    Route::post('/products/{product}/variants/update-all', [ProductVariantController::class, 'updateAll'])->name('products.variants.update');

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

    Route::get('/knowledge', [KnowledgeController::class, 'index'])->name('knowledge.index');
    Route::get('/knowledge/create', [KnowledgeController::class, 'create'])->name('knowledge.create');
    Route::post('/knowledge', [KnowledgeController::class, 'store'])->name('knowledge.store');
    Route::get('/knowledge/{knowledge}', [KnowledgeController::class, 'show'])->name('knowledge.show');
    Route::get('/knowledge/{knowledge}/edit', [KnowledgeController::class, 'edit'])->name('knowledge.edit');
    Route::put('/knowledge/{knowledge}', [KnowledgeController::class, 'update'])->name('knowledge.update');
    Route::delete('/knowledge/{knowledge}', [KnowledgeController::class, 'destroy'])->name('knowledge.destroy');

    Route::get('/therapies', [TherapyController::class, 'index'])->name('therapies.index');
    Route::get('/therapies/create', [TherapyController::class, 'create'])->name('therapies.create');
    Route::post('/therapies', [TherapyController::class, 'store'])->name('therapies.store');
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

    Route::get('/music', [MusicController::class, 'index'])->name('music.index');
    Route::get('/music/create', [MusicController::class, 'create'])->name('music.create');
    Route::post('/music', [MusicController::class, 'store'])->name('music.store');
    Route::get('/music/{music}/edit', [MusicController::class, 'edit'])->name('music.edit');
    Route::put('/music/{music}', [MusicController::class, 'update'])->name('music.update');
    Route::delete('/music/{music}', [MusicController::class, 'destroy'])->name('music.destroy');
});
