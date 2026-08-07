<?php

use App\Http\Controllers\CategoryController;
use Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::prefix('/categories')->group(function () {
        Route::controller(CategoryController::class)->group(function () {
            // Categories
            Route::get('/', 'index')->name('categories.index');
            Route::get('/create', 'create')->name('categories.create');
            Route::post('/', 'store')->name('categories.store')
                ->middleware([HandlePrecognitiveRequests::class]);
            Route::get('/{category}', 'show')->name('categories.show');
            Route::get('/{category}/edit', 'edit')->name('categories.edit');
            Route::put('/{category}', 'update')->name('categories.update')
                ->middleware([HandlePrecognitiveRequests::class]);
            Route::delete('/{category}', 'destroy')->name('categories.destroy');
        });
    });
});
