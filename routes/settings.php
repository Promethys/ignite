<?php

use App\Http\Controllers\Settings\ApiTokenController;
use App\Http\Controllers\Settings\LocaleController;
use App\Http\Controllers\Settings\PasswordController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\TwoFactorAuthenticationController;
use Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::redirect('settings', '/settings/profile');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])
        ->name('profile.update')
        ->middleware([HandlePrecognitiveRequests::class]);
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('settings/password', [PasswordController::class, 'edit'])->name('password.edit');

    Route::put('settings/password', [PasswordController::class, 'update'])
        ->middleware(['throttle:20,1', HandlePrecognitiveRequests::class])
        ->name('password.update');

    Route::get('settings/appearance', function () {
        return Inertia::render('settings/Appearance');
    })->name('appearance.edit');

    Route::get('settings/two-factor', [TwoFactorAuthenticationController::class, 'show'])
        ->name('two-factor.show');

    Route::get('settings/api-tokens', [ApiTokenController::class, 'index'])
        ->name('api-tokens.index');
    Route::post('settings/api-tokens', [ApiTokenController::class, 'store'])
        ->name('api-tokens.store')
        ->middleware([HandlePrecognitiveRequests::class]);
    Route::delete('settings/api-tokens/{token}', [ApiTokenController::class, 'destroy'])
        ->name('api-tokens.destroy');

    Route::patch('settings/locale', [LocaleController::class, 'update'])
        ->name('settings.locale.update');
});
