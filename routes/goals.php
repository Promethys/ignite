<?php

use App\Http\Controllers\Goals\GoalController;
use App\Http\Controllers\Goals\GoalEntryController;
use App\Http\Controllers\MilestoneController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
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
            Route::patch('/{goal}/uncomplete', 'uncomplete')->name('goals.uncomplete');
        });

        Route::controller(GoalEntryController::class)->group(function () {
            // Goal entries (nested resource)
            Route::get('/{goal}/entries', 'index')->name('goals.entries');
            Route::post('/{goal}/entries', 'store')->name('goals.entries.store');
            Route::put('/{goal}/entries/{goalEntry}', 'update')->name('goals.entries.update');
            Route::delete('/{goal}/entries/{goalEntry}', 'destroy')->name('goals.entries.destroy');
        });

        Route::controller(MilestoneController::class)->group(function () {
            // Goal milestones (nested resource)
            Route::post('/{goal}/milestones', 'store')->name('milestones.store');
            Route::put('/{goal}/milestones/{milestone}', 'update')->name('milestones.update');
            Route::delete('/{goal}/milestones/{milestone}', 'destroy')->name('milestones.destroy');
            Route::patch('/{goal}/milestones/{milestone}/complete', 'complete')->name('milestones.complete');
            Route::patch('/{goal}/milestones/{milestone}/uncomplete', 'uncomplete')->name('milestones.uncomplete');
        });
    });
});
