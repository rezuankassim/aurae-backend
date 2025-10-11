<?php

use App\Http\Controllers\Admin\NewsController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ProductIdentifierController;
use App\Http\Controllers\Admin\ProductInventoryController;
use App\Http\Controllers\Admin\ProductMediaController;
use App\Http\Controllers\Admin\ProductPricingController;
use App\Http\Controllers\Admin\ProductVariantController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\UserLoginActivityController;
use App\Http\Middleware\EnsureIsAdmin;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', EnsureIsAdmin::class])->as('admin.')->prefix('admin')->group(function () {
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

    Route::get('/product/{product}/variants', [ProductVariantController::class, 'index'])->name('products.variants.index');
    Route::get('/product/{product}/variants/configure', [ProductVariantController::class, 'configure'])->name('products.variants.configure');
    Route::post('/product/{product}/variants', [ProductVariantController::class, 'store'])->name('products.variants.store');
    Route::post('/product/{product}/variants/update-all', [ProductVariantController::class, 'updateAll'])->name('products.variants.update');

    Route::get('/product/{product}/pricing', [ProductPricingController::class, 'index'])->name('products.pricing.index');
    Route::post('/product/{product}/pricing', [ProductPricingController::class, 'store'])->name('products.pricing.store');

    Route::get('/products/{product}/product-identifiers', [ProductIdentifierController::class, 'index'])->name('products.identifiers.index');
    Route::post('/products/{product}/product-identifiers', [ProductIdentifierController::class, 'store'])->name('products.identifiers.store');

    Route::get('/product/{product}/inventory', [ProductInventoryController::class, 'index'])->name('products.inventory.index');
    Route::post('/product/{product}/inventory', [ProductInventoryController::class, 'store'])->name('products.inventory.store');

    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show');
    Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');

    Route::get('/users/{user}/login-activities', [UserLoginActivityController::class, 'index'])->name('users.login-activities.index');
});