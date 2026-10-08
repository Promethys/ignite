<?php

namespace Tests\Feature\Enums;

use App\Enums\AssistantProvider;
use Laravel\Ai\Enums\Lab;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AssistantProviderTest extends TestCase
{
    /**
     * @return array<string, array{AssistantProvider}>
     */
    public static function providers(): array
    {
        return collect(AssistantProvider::cases())
            ->mapWithKeys(fn (AssistantProvider $provider): array => [$provider->value => [$provider]])
            ->all();
    }

    #[DataProvider('providers')]
    public function test_every_provider_is_configured_in_the_ai_sdk_with_a_key_and_a_base_url(AssistantProvider $provider)
    {
        $configuration = config("ai.providers.{$provider->value}");

        $this->assertIsArray($configuration, "The AI SDK has no provider named [{$provider->value}].");
        $this->assertSame($provider->value, $configuration['driver']);
        $this->assertArrayHasKey('key', $configuration);
        $this->assertStringStartsWith('https://', $configuration['url']);
    }

    #[DataProvider('providers')]
    public function test_every_provider_has_a_label(AssistantProvider $provider)
    {
        $this->assertNotSame('', $provider->label());
    }

    public function test_labels_use_the_companies_own_spelling()
    {
        $this->assertSame('OpenAI', AssistantProvider::OpenAI->label());
        $this->assertSame('xAI', AssistantProvider::xAI->label());
        $this->assertSame('DeepSeek', AssistantProvider::DeepSeek->label());
    }

    #[DataProvider('providers')]
    public function test_every_case_is_named_and_valued_like_the_ai_sdks_own_enum(AssistantProvider $provider)
    {
        $lab = Lab::tryFrom($provider->value);

        $this->assertNotNull($lab, "The AI SDK has no provider with the value [{$provider->value}].");
        $this->assertSame($lab->name, $provider->name);
    }
}
