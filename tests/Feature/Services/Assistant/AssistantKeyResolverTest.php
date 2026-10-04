<?php

namespace Tests\Feature\Services\Assistant;

use App\Enums\AssistantProvider;
use App\Models\AssistantKey;
use App\Models\User;
use App\Services\Assistant\AssistantKeyResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AssistantKeyResolverTest extends TestCase
{
    use RefreshDatabase;

    private AssistantKeyResolver $resolver;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = app(AssistantKeyResolver::class);
        $this->user = User::factory()->create();
    }

    private function configureInstance(string $provider, ?string $key): void
    {
        config(['ai.default' => $provider, "ai.providers.{$provider}.key" => $key]);
    }

    // =========================================================================
    // RESOLVE
    // =========================================================================

    public function test_nothing_resolves_without_a_key_or_an_instance_configuration()
    {
        $this->assertNull($this->resolver->resolve($this->user));
        $this->assertFalse($this->resolver->isAvailableFor($this->user));
    }

    public function test_the_users_default_key_resolves_with_its_provider_settings_and_model()
    {
        AssistantKey::factory()->provider(AssistantProvider::OpenAi)->create(['user_id' => $this->user->id]);
        AssistantKey::factory()->provider(AssistantProvider::Anthropic)->default()->create([
            'user_id' => $this->user->id,
            'api_key' => 'sk-ant-users-own-key',
            'model' => 'claude-custom',
        ]);

        $connection = $this->resolver->resolve($this->user->fresh());

        $this->assertSame('anthropic', $connection->providerConfiguration['driver']);
        $this->assertSame('sk-ant-users-own-key', $connection->providerConfiguration['key']);
        $this->assertSame(config('ai.providers.anthropic.url'), $connection->providerConfiguration['url']);
        $this->assertSame(config('ai.providers.anthropic.version'), $connection->providerConfiguration['version']);
        $this->assertSame('claude-custom', $connection->model);
    }

    public function test_the_users_key_wins_over_the_instance_configuration()
    {
        $this->configureInstance('gemini', 'instance-gemini-key');
        AssistantKey::factory()->provider(AssistantProvider::OpenAi)->create([
            'user_id' => $this->user->id,
            'api_key' => 'sk-users-own-key',
        ]);

        $connection = $this->resolver->resolve($this->user);

        $this->assertSame('openai', $connection->providerConfiguration['driver']);
        $this->assertSame('sk-users-own-key', $connection->providerConfiguration['key']);
    }

    public function test_the_instance_configuration_resolves_for_a_user_without_a_key()
    {
        $this->configureInstance('gemini', 'instance-gemini-key');

        $connection = $this->resolver->resolve($this->user);

        $this->assertSame('gemini', $connection->providerConfiguration['driver']);
        $this->assertSame('instance-gemini-key', $connection->providerConfiguration['key']);
        $this->assertNull($connection->model);
    }

    public function test_one_users_key_never_resolves_for_another_user()
    {
        AssistantKey::factory()->provider(AssistantProvider::OpenAi)->create();

        $this->assertNull($this->resolver->resolve($this->user));
    }

    // =========================================================================
    // INSTANCE CONFIGURATION
    // =========================================================================

    public function test_the_instance_is_not_configured_until_a_default_provider_is_named()
    {
        config(['ai.default' => null, 'ai.providers.openai.key' => 'sk-present-but-not-selected']);

        $this->assertFalse($this->resolver->instanceIsConfigured());
    }

    public function test_a_default_provider_without_its_key_does_not_count()
    {
        $this->configureInstance('openai', null);

        $this->assertFalse($this->resolver->instanceIsConfigured());
        $this->assertNull($this->resolver->resolve($this->user));
    }

    public function test_an_unknown_default_provider_does_not_count()
    {
        config(['ai.default' => 'not-a-provider']);

        $this->assertFalse($this->resolver->instanceIsConfigured());
    }

    public function test_ollama_counts_as_configured_without_a_key()
    {
        $this->configureInstance('ollama', '');

        $connection = $this->resolver->resolve($this->user);

        $this->assertTrue($this->resolver->instanceIsConfigured());
        $this->assertSame('ollama', $connection->providerConfiguration['driver']);
    }

    // =========================================================================
    // AVAILABILITY
    // =========================================================================

    public function test_availability_follows_the_users_default_key()
    {
        AssistantKey::factory()->create(['user_id' => $this->user->id]);

        $this->assertTrue($this->resolver->isAvailableFor($this->user));
    }

    public function test_availability_is_answered_without_reading_any_key()
    {
        AssistantKey::factory()->create(['user_id' => $this->user->id]);
        DB::enableQueryLog();

        $this->resolver->isAvailableFor($this->user);

        $queries = collect(DB::getQueryLog())->pluck('query');
        $this->assertCount(1, $queries);
        $this->assertStringContainsString('exists', $queries->first());
        $this->assertStringNotContainsString('user_data_keys', $queries->implode(' '));
    }

    public function test_an_instance_configuration_makes_the_assistant_available_with_no_query()
    {
        $this->configureInstance('gemini', 'instance-gemini-key');
        DB::enableQueryLog();

        $this->assertTrue($this->resolver->isAvailableFor($this->user));
        $this->assertEmpty(DB::getQueryLog());
    }
}
