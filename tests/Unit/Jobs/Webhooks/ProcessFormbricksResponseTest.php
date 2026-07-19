<?php

namespace Tests\Unit\Jobs\Webhooks;

use App\Jobs\Webhooks\ProcessFormbricksResponse;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ProcessFormbricksResponseTest extends TestCase
{
    private const WEBHOOK_URL = 'https://discord.com/api/webhooks/test/token';

    private function fixture(): array
    {
        return json_decode(
            file_get_contents(base_path('tests/Fixtures/webhooks/formbricks/response-finished.json')),
            true
        );
    }

    public function test_formats_and_sends_the_response_to_discord()
    {
        config(['services.discord.ops_webhook_url' => self::WEBHOOK_URL]);
        Http::fake();

        (new ProcessFormbricksResponse($this->fixture()))->handle();

        Http::assertSent(fn ($request) => $request->url() === self::WEBHOOK_URL
            && $request['content'] === 'New feedback response: Feedback Box'
            && $request['embeds'][0]['title'] === 'Feedback Box');
    }

    public function test_does_nothing_for_an_empty_payload()
    {
        config(['services.discord.ops_webhook_url' => self::WEBHOOK_URL]);
        Http::fake();

        (new ProcessFormbricksResponse([]))->handle();

        Http::assertNothingSent();
    }
}
