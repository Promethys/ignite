<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Goals\GoalController;
use App\Http\Controllers\Goals\GoalEntryController;

Route::middleware('auth')->group(function () {
    Route::prefix('/goals')->group(function () {
        Route::controller(GoalController::class)->group(function () {
            // Goals
            Route::get('/', 'index')->name('goals.index');
            Route::get('/create', 'create')->name('goals.create');
            Route::post('/', 'store')->name('goals.store');
            Route::get('/{goal}', 'show')->name('goals.show');
            Route::get('/{goal}/edit', 'edit')->name('goals.edit');
            Route::put('/{goal}', 'update')->name('goals.update');
            Route::delete('/{goal}', 'destroy')->name('goals.destroy');

            // Quick actions (optional, for better UX)
            Route::patch('/{goal}/status', 'updateStatus')->name('goals.update-status');
            Route::patch('/{goal}/complete', 'complete')->name('goals.complete');
        });

        Route::controller(GoalEntryController::class)->group(function () {
            // Goal entries (nested resource)
            Route::post('/{goal}/entries', 'store')->name('goals.entries.store');
            Route::delete('/{goal}/entries/{entry}', 'destroy')->name('goals.entries.destroy');
        });
    });
});
