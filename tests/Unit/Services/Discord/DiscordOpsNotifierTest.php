<?php

namespace Tests\Unit\Services\Discord;

use App\Services\Discord\DiscordOpsNotifier;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class DiscordOpsNotifierTest extends TestCase
{
    private const WEBHOOK_URL = 'https://discord.com/api/webhooks/test/token';

    public function test_posts_the_payload_to_the_configured_webhook()
    {
        config(['services.discord.ops_webhook_url' => self::WEBHOOK_URL]);
        Http::fake();

        DiscordOpsNotifier::send(['content' => 'hello']);

        Http::assertSent(fn ($request) => $request->url() === self::WEBHOOK_URL
            && $request['content'] === 'hello');
    }

    public function test_does_not_post_an_empty_payload()
    {
        config(['services.discord.ops_webhook_url' => self::WEBHOOK_URL]);
        Http::fake();

        DiscordOpsNotifier::send([]);

        Http::assertNothingSent();
    }

    public function test_skips_and_logs_when_the_url_is_missing()
    {
        config(['services.discord.ops_webhook_url' => null]);
        Http::fake();
        Log::spy();

        DiscordOpsNotifier::send(['content' => 'hello']);

        Http::assertNothingSent();
        Log::shouldHaveReceived('warning')->once();
    }

    public function test_logs_when_discord_responds_with_a_failure()
    {
        config(['services.discord.ops_webhook_url' => self::WEBHOOK_URL]);
        Http::fake([self::WEBHOOK_URL => Http::response('', 500)]);
        Log::spy();

        DiscordOpsNotifier::send(['content' => 'hello']);

        Log::shouldHaveReceived('error')->once();
    }
}
