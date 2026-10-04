<?php

namespace App\Providers;

use App\Ai\Storage\EncryptedConversationStore;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Ai\Contracts\ConversationStore;

class AssistantServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(EncryptedConversationStore::class, fn (): EncryptedConversationStore => new EncryptedConversationStore(
            config('ai.conversations.connection'),
        ));
        $this->app->singleton(ConversationStore::class, fn ($app): EncryptedConversationStore => $app->make(EncryptedConversationStore::class));
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        RateLimiter::for('assistant', fn (Request $request) => Limit::perMinute(20)->by($request->user()->id));
    }
}
