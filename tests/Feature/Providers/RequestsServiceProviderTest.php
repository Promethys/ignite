<?php

namespace Tests\Feature\Providers;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RequestsServiceProviderTest extends TestCase
{
    public function test_outbound_requests_carry_the_global_user_agent_header(): void
    {
        Http::fake();

        Http::get('https://example.test');

        $expected = config('app.name').'/'.config('app.version').' (+'.config('app.url').')';

        Http::assertSent(fn ($request) => $request->hasHeader('User-Agent', $expected));
    }

    public function test_the_user_agent_reflects_the_configured_app_version(): void
    {
        config(['app.version' => '9.9']);

        Http::fake();

        Http::get('https://example.test');

        Http::assertSent(fn ($request) => str_contains($request->header('User-Agent')[0], '/9.9 (+'));
    }
}
