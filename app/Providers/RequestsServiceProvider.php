<?php

namespace App\Providers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\ServiceProvider;

class RequestsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        Http::globalRequestMiddleware(
            fn ($request) => $request->withHeader(
                'User-Agent',
                config('app.name').'/'.config('app.version').' (+'.config('app.url').')'
            )
        );
    }
}
