<?php

namespace Tests\Feature\Webhooks;

use App\Jobs\Webhooks\ProcessFormbricksResponse;
use Illuminate\Support\Facades\Bus;
use Svix\Webhook;
use Tests\TestCase;

class FormbricksWebhookTest extends TestCase
{
    private const SECRET = 'whsec_MfKQ9r8GKYqrTwjUPD8ILPZIo2LaLaSw';

    private const ENDPOINT = '/api/webhooks/formbricks';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.formbricks.webhook_secret' => self::SECRET,
            'services.discord.ops_enabled' => true,
        ]);
    }

    private function body(): string
    {
        return json_encode([
            'event' => 'responseFinished',
            'data' => ['id' => 'resp_1', 'survey' => ['title' => 'Feedback']],
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function signedHeaders(string $body, ?int $timestamp = null, string $id = 'msg_1'): array
    {
        $timestamp ??= time();
        $signature = (new Webhook(self::SECRET))->sign($id, $timestamp, $body);

        return [
            'Content-Type' => 'application/json',
            'webhook-id' => $id,
            'webhook-timestamp' => (string) $timestamp,
            'webhook-signature' => $signature,
        ];
    }

    private function postWebhook(string $body, array $headers)
    {
        return $this->call(
            'POST', self::ENDPOINT, [], [], [],
            $this->transformHeadersToServerVars($headers), $body
        );
    }

    public function test_processes_a_valid_signed_webhook()
    {
        Bus::fake();
        $body = $this->body();

        $response = $this->postWebhook($body, $this->signedHeaders($body));

        $response->assertNoContent();
        Bus::assertDispatchedAfterResponse(ProcessFormbricksResponse::class);
    }

    public function test_rejects_a_tampered_body()
    {
        Bus::fake();
        $body = $this->body();
        $headers = $this->signedHeaders($body);

        $response = $this->postWebhook($body.' ', $headers);

        $response->assertStatus(400);
        Bus::assertNotDispatchedAfterResponse(ProcessFormbricksResponse::class);
    }

    public function test_rejects_a_stale_timestamp()
    {
        Bus::fake();
        $body = $this->body();

        $response = $this->postWebhook($body, $this->signedHeaders($body, time() - 600));

        $response->assertStatus(400);
        Bus::assertNotDispatchedAfterResponse(ProcessFormbricksResponse::class);
    }

    public function test_rejects_missing_signature_headers()
    {
        Bus::fake();

        $response = $this->postWebhook($this->body(), ['Content-Type' => 'application/json']);

        $response->assertStatus(400);
        Bus::assertNotDispatchedAfterResponse(ProcessFormbricksResponse::class);
    }

    public function test_ignores_a_duplicate_delivery()
    {
        Bus::fake();
        $body = $this->body();
        $headers = $this->signedHeaders($body, id: 'msg_dup');

        $this->postWebhook($body, $headers)->assertNoContent();
        $this->postWebhook($body, $headers)->assertNoContent();

        Bus::assertDispatchedAfterResponseTimes(ProcessFormbricksResponse::class, 1);
    }

    public function test_does_nothing_when_disabled()
    {
        config(['services.discord.ops_enabled' => false]);
        Bus::fake();
        $body = $this->body();

        $response = $this->postWebhook($body, $this->signedHeaders($body));

        $response->assertNoContent();
        Bus::assertNotDispatchedAfterResponse(ProcessFormbricksResponse::class);
    }
}
