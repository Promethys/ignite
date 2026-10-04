<?php

use App\Http\Controllers\AssistantChatController;
use App\Http\Controllers\AssistantConversationController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::post('assistant/chat', AssistantChatController::class)
        ->middleware('throttle:assistant')
        ->name('assistant.chat');
    Route::get('assistant/conversations', [AssistantConversationController::class, 'index'])
        ->name('assistant.conversations.index');
    Route::get('assistant/conversations/{conversation}', [AssistantConversationController::class, 'show'])
        ->name('assistant.conversations.show');
});

require __DIR__.'/settings.php';
require __DIR__.'/goals.php';
require __DIR__.'/categories.php';
require __DIR__.'/auth.php';
