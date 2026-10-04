<?php

namespace Tests\Feature\Settings;

use App\Enums\AssistantProvider;
use App\Models\AssistantKey;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AssistantKeyTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return [
            'provider' => 'openai',
            'api_key' => 'sk-test-0123456789abcd',
            'model' => null,
            'consent' => true,
            ...$overrides,
        ];
    }

    // =========================================================================
    // ACCESS
    // =========================================================================

    public function test_guests_get_redirected_to_login(): void
    {
        $this->actingAsGuest()->get('/settings/assistant')->assertRedirect('/login');
        $this->actingAsGuest()->post('/settings/assistant')->assertRedirect('/login');
    }

    public function test_the_page_lists_every_provider_and_the_users_keys_without_the_key_itself(): void
    {
        AssistantKey::factory()->provider(AssistantProvider::Anthropic)->create([
            'user_id' => $this->user->id,
            'api_key' => 'sk-ant-secret-value-wxyz',
            'key_suffix' => 'wxyz',
        ]);
        AssistantKey::factory()->provider(AssistantProvider::OpenAi)->create();

        $response = $this->actingAs($this->user)->get('/settings/assistant');

        $response->assertInertia(fn (Assert $page) => $page
            ->component('settings/Assistant')
            ->has('providers', count(AssistantProvider::cases()))
            ->where('providers.0', ['value' => 'anthropic', 'label' => 'Anthropic'])
            ->has('assistantKeys', 1)
            ->where('assistantKeys.0.provider', 'anthropic')
            ->where('assistantKeys.0.key_suffix', 'wxyz')
            ->where('assistantKeys.0.is_default', true)
            ->missing('assistantKeys.0.api_key')
        );
        $this->assertStringNotContainsString('sk-ant-secret-value-wxyz', $response->getContent());
    }

    // =========================================================================
    // CONNECT
    // =========================================================================

    public function test_a_key_the_provider_accepts_is_saved_encrypted_as_the_default(): void
    {
        Http::fake(['*' => Http::response(['data' => []])]);

        $this->actingAs($this->user)
            ->post('/settings/assistant', $this->validPayload(['model' => 'gpt-custom']))
            ->assertSessionHasNoErrors()
            ->assertInertiaFlash('toast.type', 'success')
            ->assertInertiaFlash('toast.message', 'OpenAI key saved.');

        $assistantKey = $this->user->assistantKeys()->sole();

        $this->assertSame(AssistantProvider::OpenAi, $assistantKey->provider);
        $this->assertSame('sk-test-0123456789abcd', $assistantKey->api_key);
        $this->assertSame('abcd', $assistantKey->key_suffix);
        $this->assertSame('gpt-custom', $assistantKey->model);
        $this->assertTrue($assistantKey->is_default);
        $this->assertNotNull($assistantKey->consented_at);
        $this->assertStringStartsWith('v1:', DB::table('assistant_keys')->value('api_key'));
    }

    public function test_a_key_the_provider_rejects_is_not_saved(): void
    {
        Http::fake(['*' => Http::response([], 401)]);

        $this->actingAs($this->user)
            ->post('/settings/assistant', $this->validPayload())
            ->assertSessionHasErrors(['api_key' => 'OpenAI did not accept this key. Check that you copied it completely.']);

        $this->assertDatabaseCount('assistant_keys', 0);
    }

    public function test_a_key_is_not_saved_when_the_provider_cannot_be_reached(): void
    {
        Http::fake(['*' => Http::response([], 500)]);

        $this->actingAs($this->user)
            ->post('/settings/assistant', $this->validPayload())
            ->assertSessionHasErrors(['api_key' => 'Ignite could not reach OpenAI to check this key. Try again in a moment.']);

        $this->assertDatabaseCount('assistant_keys', 0);
    }

    public function test_connecting_requires_consent(): void
    {
        Http::fake();

        $this->actingAs($this->user)
            ->post('/settings/assistant', $this->validPayload(['consent' => false]))
            ->assertSessionHasErrors('consent');

        Http::assertNothingSent();
        $this->assertDatabaseCount('assistant_keys', 0);
    }

    public function test_connecting_rejects_an_unknown_provider(): void
    {
        Http::fake();

        $this->actingAs($this->user)
            ->post('/settings/assistant', $this->validPayload(['provider' => 'cohere']))
            ->assertSessionHasErrors('provider');

        Http::assertNothingSent();
    }

    public function test_connecting_a_provider_twice_is_rejected(): void
    {
        Http::fake();

        AssistantKey::factory()->provider(AssistantProvider::OpenAi)->create(['user_id' => $this->user->id]);

        $this->actingAs($this->user)
            ->post('/settings/assistant', $this->validPayload())
            ->assertSessionHasErrors('provider');

        Http::assertNothingSent();
        $this->assertDatabaseCount('assistant_keys', 1);
    }

    public function test_another_users_key_for_the_same_provider_does_not_block_connecting(): void
    {
        AssistantKey::factory()->provider(AssistantProvider::OpenAi)->create();
        Http::fake(['*' => Http::response([])]);

        $this->actingAs($this->user)
            ->post('/settings/assistant', $this->validPayload())
            ->assertSessionHasNoErrors();

        $this->assertSame(1, $this->user->assistantKeys()->count());
    }

    public function test_live_validation_never_calls_the_provider(): void
    {
        Http::fake();

        $this->actingAs($this->user)
            ->withHeaders(['Precognition' => 'true', 'Precognition-Validate-Only' => 'api_key'])
            ->post('/settings/assistant', $this->validPayload())
            ->assertNoContent();

        Http::assertNothingSent();
        $this->assertDatabaseCount('assistant_keys', 0);
    }

    // =========================================================================
    // EDIT
    // =========================================================================

    public function test_editing_with_a_blank_key_only_changes_the_model(): void
    {
        Http::fake();

        $assistantKey = AssistantKey::factory()->provider(AssistantProvider::OpenAi)->create([
            'user_id' => $this->user->id,
            'api_key' => 'sk-test-original-wxyz',
            'key_suffix' => 'wxyz',
        ]);

        $this->actingAs($this->user)
            ->put("/settings/assistant/{$assistantKey->id}", ['api_key' => '', 'model' => 'gpt-stronger'])
            ->assertSessionHasNoErrors()
            ->assertInertiaFlash('toast.message', 'OpenAI key updated.');

        Http::assertNothingSent();
        $this->assertSame('sk-test-original-wxyz', $assistantKey->fresh()->api_key);
        $this->assertSame('wxyz', $assistantKey->fresh()->key_suffix);
        $this->assertSame('gpt-stronger', $assistantKey->fresh()->model);
    }

    public function test_editing_with_a_new_key_checks_it_and_replaces_the_stored_one(): void
    {
        $assistantKey = AssistantKey::factory()->provider(AssistantProvider::OpenAi)->create(['user_id' => $this->user->id]);
        Http::fake(['*' => Http::response([])]);

        $this->actingAs($this->user)
            ->put("/settings/assistant/{$assistantKey->id}", ['api_key' => 'sk-test-replacement-9876', 'model' => null])
            ->assertSessionHasNoErrors();

        Http::assertSentCount(1);
        $this->assertSame('sk-test-replacement-9876', $assistantKey->fresh()->api_key);
        $this->assertSame('9876', $assistantKey->fresh()->key_suffix);
    }

    public function test_editing_with_a_rejected_key_keeps_the_stored_one(): void
    {
        $assistantKey = AssistantKey::factory()->provider(AssistantProvider::OpenAi)->create([
            'user_id' => $this->user->id,
            'api_key' => 'sk-test-original-wxyz',
        ]);
        Http::fake(['*' => Http::response([], 401)]);

        $this->actingAs($this->user)
            ->put("/settings/assistant/{$assistantKey->id}", ['api_key' => 'sk-test-wrong', 'model' => null])
            ->assertSessionHasErrors('api_key');

        $this->assertSame('sk-test-original-wxyz', $assistantKey->fresh()->api_key);
    }

    // =========================================================================
    // DEFAULT AND REMOVE
    // =========================================================================

    public function test_a_user_can_choose_which_key_the_assistant_uses(): void
    {
        $first = AssistantKey::factory()->provider(AssistantProvider::OpenAi)->create(['user_id' => $this->user->id]);
        $second = AssistantKey::factory()->provider(AssistantProvider::Anthropic)->create(['user_id' => $this->user->id]);

        $this->actingAs($this->user)
            ->patch("/settings/assistant/{$second->id}/default")
            ->assertInertiaFlash('toast.message', 'The assistant now uses Anthropic.');

        $this->assertTrue($second->fresh()->is_default);
        $this->assertFalse($first->fresh()->is_default);
    }

    public function test_removing_a_key_deletes_it_and_promotes_another(): void
    {
        $default = AssistantKey::factory()->provider(AssistantProvider::OpenAi)->create(['user_id' => $this->user->id]);
        $other = AssistantKey::factory()->provider(AssistantProvider::Anthropic)->create(['user_id' => $this->user->id]);

        $this->actingAs($this->user)
            ->delete("/settings/assistant/{$default->id}")
            ->assertInertiaFlash('toast.message', 'OpenAI key removed.');

        $this->assertModelMissing($default);
        $this->assertTrue($other->fresh()->is_default);
    }

    public function test_another_users_key_cannot_be_edited_chosen_or_removed(): void
    {
        $othersKey = AssistantKey::factory()->provider(AssistantProvider::OpenAi)->create(['model' => 'untouched']);

        $this->actingAs($this->user)->put("/settings/assistant/{$othersKey->id}", ['model' => 'hijacked'])->assertNotFound();
        $this->actingAs($this->user)->patch("/settings/assistant/{$othersKey->id}/default")->assertNotFound();
        $this->actingAs($this->user)->delete("/settings/assistant/{$othersKey->id}")->assertNotFound();

        $this->assertSame('untouched', $othersKey->fresh()->model);
        $this->assertModelExists($othersKey);
    }

    public function test_the_page_says_whether_the_instance_is_already_configured(): void
    {
        $this->actingAs($this->user)
            ->get('/settings/assistant')
            ->assertInertia(fn (Assert $page) => $page->where('instanceConfigured', false));

        config(['ai.default' => 'gemini', 'ai.providers.gemini.key' => 'instance-gemini-key']);

        $this->actingAs($this->user)
            ->get('/settings/assistant')
            ->assertInertia(fn (Assert $page) => $page->where('instanceConfigured', true));
    }
}
