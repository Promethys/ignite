<?php

namespace Tests\Feature\Services\Assistant;

use App\Enums\AssistantKeyCheck;
use App\Enums\AssistantProvider;
use App\Services\Assistant\AssistantKeyChecker;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AssistantKeyCheckerTest extends TestCase
{
    private AssistantKeyChecker $checker;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
        $this->checker = app(AssistantKeyChecker::class);
    }

    /**
     * @return array<string, array{AssistantProvider, string}>
     */
    public static function checkUrls(): array
    {
        return [
            'anthropic' => [AssistantProvider::Anthropic, 'https://api.anthropic.com/v1/models'],
            'deepseek' => [AssistantProvider::DeepSeek, 'https://api.deepseek.com/v1/models'],
            'gemini' => [AssistantProvider::Gemini, 'https://generativelanguage.googleapis.com/v1beta/models'],
            'groq' => [AssistantProvider::Groq, 'https://api.groq.com/openai/v1/models'],
            'mistral' => [AssistantProvider::Mistral, 'https://api.mistral.ai/v1/models'],
            'openai' => [AssistantProvider::OpenAI, 'https://api.openai.com/v1/models'],
            'openrouter' => [AssistantProvider::OpenRouter, 'https://openrouter.ai/api/v1/key'],
            'xai' => [AssistantProvider::xAI, 'https://api.x.ai/v1/models'],
        ];
    }

    public function test_every_provider_has_a_check_url()
    {
        $this->assertSameSize(AssistantProvider::cases(), self::checkUrls());
    }

    #[DataProvider('checkUrls')]
    public function test_it_sends_a_get_request_to_the_providers_check_url(AssistantProvider $provider, string $url)
    {
        Http::fake(['*' => Http::response(['data' => []])]);

        $this->assertSame(AssistantKeyCheck::Valid, $this->checker->check($provider, 'the-key'));

        Http::assertSent(fn (Request $request): bool => $request->method() === 'GET' && $request->url() === $url);
        Http::assertSentCount(1);
    }

    public function test_most_providers_receive_the_key_as_a_bearer_token()
    {
        Http::fake(['*' => Http::response([])]);

        $this->checker->check(AssistantProvider::OpenAI, 'the-key');

        Http::assertSent(fn (Request $request): bool => $request->hasHeader('Authorization', 'Bearer the-key'));
    }

    public function test_anthropic_also_receives_the_api_version_header()
    {
        Http::fake(['*' => Http::response([])]);

        $this->checker->check(AssistantProvider::Anthropic, 'the-key');

        Http::assertSent(fn (Request $request): bool => $request->hasHeader('Authorization', 'Bearer the-key')
            && $request->hasHeader('anthropic-version', config('ai.providers.anthropic.version')));
    }

    public function test_gemini_receives_the_key_in_its_own_header_and_never_in_the_url()
    {
        Http::fake(['*' => Http::response([])]);

        $this->checker->check(AssistantProvider::Gemini, 'the-key');

        Http::assertSent(fn (Request $request): bool => $request->hasHeader('x-goog-api-key', 'the-key')
            && ! $request->hasHeader('Authorization')
            && ! str_contains($request->url(), 'the-key'));
    }

    /**
     * @return array<string, array{int, AssistantKeyCheck}>
     */
    public static function outcomesByStatus(): array
    {
        return [
            'accepted' => [200, AssistantKeyCheck::Valid],
            'bad request, how Gemini and xAI reject a key' => [400, AssistantKeyCheck::Invalid],
            'unauthorized' => [401, AssistantKeyCheck::Invalid],
            'forbidden' => [403, AssistantKeyCheck::Invalid],
            'rate limited' => [429, AssistantKeyCheck::Unreachable],
            'server error' => [500, AssistantKeyCheck::Unreachable],
        ];
    }

    #[DataProvider('outcomesByStatus')]
    public function test_the_response_status_decides_the_outcome(int $status, AssistantKeyCheck $expected)
    {
        Http::fake(['*' => Http::response([], $status)]);

        $this->assertSame($expected, $this->checker->check(AssistantProvider::OpenAI, 'the-key'));
    }

    public function test_a_connection_failure_is_unreachable_not_invalid()
    {
        Http::fake(fn () => throw new ConnectionException('Connection timed out'));

        $this->assertSame(AssistantKeyCheck::Unreachable, $this->checker->check(AssistantProvider::OpenAI, 'the-key'));
    }

    public function test_a_base_url_from_the_ai_config_is_used_without_a_doubled_slash()
    {
        config(['ai.providers.gemini.url' => 'https://proxy.example.test/v1beta/']);
        Http::fake(['*' => Http::response([])]);

        $this->checker->check(AssistantProvider::Gemini, 'the-key');

        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://proxy.example.test/v1beta/models');
    }
}
