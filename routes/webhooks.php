<?php

use App\Http\Controllers\Webhooks\FormbricksController;
use Illuminate\Support\Facades\Route;

Route::prefix('/webhooks')
    ->name('webhooks.')
    ->group(function () {
        Route::post('/formbricks', [FormbricksController::class, 'handle'])
            ->middleware('webhook.signature:formbricks')
            ->name('formbricks.handle');
    });
