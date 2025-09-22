<?php

use App\Http\Controllers\Admin\NewsController;
use App\Http\Middleware\EnsureIsAdmin;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', EnsureIsAdmin::class])->as('admin.')->prefix('admin')->group(function () {
  Route::get('/news', [NewsController::class, 'index'])->name('news.index');
  Route::get('/news/create', [NewsController::class, 'create'])->name('news.create');
  Route::post('/news', [NewsController::class, 'store'])->name('news.store');
  Route::get('/news/{news}/edit', [NewsController::class, 'edit'])->name('news.edit');
  Route::put('/news/{news}', [NewsController::class, 'update'])->name('news.update');
  Route::delete('/news/{news}', [NewsController::class, 'destroy'])->name('news.destroy');
});