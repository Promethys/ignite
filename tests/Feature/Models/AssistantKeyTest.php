<?php

namespace Tests\Feature\Models;

use App\Enums\AssistantProvider;
use App\Models\AssistantKey;
use App\Models\User;
use App\Services\Encryption\UserDataCipher;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AssistantKeyTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // RELATIONSHIP TESTS
    // =========================================================================

    public function test_assistant_key_belongs_to_user()
    {
        $user = User::factory()->create();
        $assistantKey = AssistantKey::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $assistantKey->user);
        $this->assertTrue($assistantKey->user->is($user));
    }

    public function test_a_user_cannot_have_two_keys_for_the_same_provider()
    {
        $user = User::factory()->create();
        AssistantKey::factory()->provider(AssistantProvider::OpenAi)->create(['user_id' => $user->id]);

        $this->expectException(QueryException::class);

        AssistantKey::factory()->provider(AssistantProvider::OpenAi)->create(['user_id' => $user->id]);
    }

    public function test_a_user_can_keep_a_key_for_several_providers()
    {
        $user = User::factory()->create();
        AssistantKey::factory()->provider(AssistantProvider::OpenAi)->create(['user_id' => $user->id]);
        AssistantKey::factory()->provider(AssistantProvider::Anthropic)->create(['user_id' => $user->id]);

        $this->assertCount(2, $user->assistantKeys);
    }

    public function test_two_users_can_each_have_a_key_for_the_same_provider()
    {
        AssistantKey::factory()->provider(AssistantProvider::OpenAi)->create();
        AssistantKey::factory()->provider(AssistantProvider::OpenAi)->create();

        $this->assertDatabaseCount('assistant_keys', 2);
    }

    public function test_the_assistant_key_is_deleted_with_its_user()
    {
        $assistantKey = AssistantKey::factory()->create();

        $assistantKey->user->delete();

        $this->assertDatabaseMissing('assistant_keys', ['id' => $assistantKey->id]);
    }

    // =========================================================================
    // DEFAULT KEY TESTS
    // =========================================================================

    public function test_the_first_key_of_a_user_becomes_the_default()
    {
        $user = User::factory()->create();

        $first = AssistantKey::factory()->provider(AssistantProvider::OpenAi)->create(['user_id' => $user->id]);
        $second = AssistantKey::factory()->provider(AssistantProvider::Anthropic)->create(['user_id' => $user->id]);

        $this->assertTrue($first->fresh()->is_default);
        $this->assertFalse($second->fresh()->is_default);
    }

    public function test_marking_a_key_as_default_clears_the_flag_on_the_users_other_keys()
    {
        $user = User::factory()->create();
        $first = AssistantKey::factory()->provider(AssistantProvider::OpenAi)->create(['user_id' => $user->id]);
        $second = AssistantKey::factory()->provider(AssistantProvider::Anthropic)->create(['user_id' => $user->id]);

        $second->markAsDefault();

        $this->assertFalse($first->fresh()->is_default);
        $this->assertTrue($second->fresh()->is_default);
        $this->assertSame(1, $user->assistantKeys()->where('is_default', true)->count());
    }

    public function test_marking_a_default_leaves_other_users_keys_alone()
    {
        $otherUsersDefault = AssistantKey::factory()->provider(AssistantProvider::OpenAi)->create();
        $user = User::factory()->create();
        AssistantKey::factory()->provider(AssistantProvider::OpenAi)->create(['user_id' => $user->id]);
        $second = AssistantKey::factory()->provider(AssistantProvider::Anthropic)->create(['user_id' => $user->id]);

        $second->markAsDefault();

        $this->assertTrue($otherUsersDefault->fresh()->is_default);
    }

    public function test_deleting_the_default_promotes_the_most_recently_added_key()
    {
        $user = User::factory()->create();
        $default = AssistantKey::factory()->provider(AssistantProvider::OpenAi)->create(['user_id' => $user->id]);
        $older = AssistantKey::factory()->provider(AssistantProvider::Anthropic)->create(['user_id' => $user->id]);
        $newest = AssistantKey::factory()->provider(AssistantProvider::Gemini)->create(['user_id' => $user->id]);

        $default->delete();

        $this->assertTrue($newest->fresh()->is_default);
        $this->assertFalse($older->fresh()->is_default);
    }

    public function test_deleting_a_key_that_is_not_the_default_keeps_the_default()
    {
        $user = User::factory()->create();
        $default = AssistantKey::factory()->provider(AssistantProvider::OpenAi)->create(['user_id' => $user->id]);
        $other = AssistantKey::factory()->provider(AssistantProvider::Anthropic)->create(['user_id' => $user->id]);

        $other->delete();

        $this->assertTrue($default->fresh()->is_default);
    }

    public function test_deleting_the_only_key_leaves_no_default()
    {
        $assistantKey = AssistantKey::factory()->create();
        $user = $assistantKey->user;

        $assistantKey->delete();

        $this->assertNull($user->fresh()->defaultAssistantKey);
    }

    public function test_the_default_flag_cannot_be_mass_assigned()
    {
        $user = User::factory()->create();
        AssistantKey::factory()->provider(AssistantProvider::OpenAi)->create(['user_id' => $user->id]);

        $second = $user->assistantKeys()->create([
            'provider' => AssistantProvider::Anthropic,
            'api_key' => 'sk-test-secret-value',
            'key_suffix' => 'alue',
            'consented_at' => now(),
            'is_default' => true,
        ]);

        $this->assertFalse($second->fresh()->is_default);
    }

    // =========================================================================
    // ENCRYPTION TESTS
    // =========================================================================

    public function test_the_api_key_is_stored_encrypted_and_read_back_in_clear()
    {
        $assistantKey = AssistantKey::factory()->create(['api_key' => 'sk-test-secret-value']);

        $stored = DB::table('assistant_keys')->where('id', $assistantKey->id)->value('api_key');

        $this->assertStringStartsWith(UserDataCipher::PREFIX, $stored);
        $this->assertStringNotContainsString('sk-test-secret-value', $stored);
        $this->assertSame('sk-test-secret-value', AssistantKey::find($assistantKey->id)->api_key);
    }

    public function test_the_api_key_is_encrypted_with_its_owners_data_key()
    {
        $assistantKey = AssistantKey::factory()->create(['api_key' => 'sk-test-secret-value']);
        $stored = DB::table('assistant_keys')->where('id', $assistantKey->id)->value('api_key');

        $this->assertSame(
            'sk-test-secret-value',
            app(UserDataCipher::class)->decrypt($stored, $assistantKey->user->data_key_id),
        );
    }

    public function test_the_api_key_never_appears_in_serialized_output()
    {
        $assistantKey = AssistantKey::factory()->create(['api_key' => 'sk-test-secret-value']);

        $this->assertArrayNotHasKey('api_key', $assistantKey->toArray());
        $this->assertStringNotContainsString('sk-test-secret-value', $assistantKey->toJson());
    }

    // =========================================================================
    // CAST AND MASS ASSIGNMENT TESTS
    // =========================================================================

    public function test_provider_is_cast_to_the_enum()
    {
        $assistantKey = AssistantKey::factory()->provider(AssistantProvider::Gemini)->create();

        $this->assertSame(AssistantProvider::Gemini, $assistantKey->fresh()->provider);
        $this->assertDatabaseHas('assistant_keys', ['id' => $assistantKey->id, 'provider' => 'gemini']);
    }

    public function test_consented_at_is_cast_to_a_datetime()
    {
        $assistantKey = AssistantKey::factory()->create();

        $this->assertInstanceOf(Carbon::class, $assistantKey->fresh()->consented_at);
    }

    public function test_the_owner_cannot_be_mass_assigned()
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $assistantKey = $owner->assistantKeys()->create([
            'user_id' => $other->id,
            'provider' => AssistantProvider::OpenAi,
            'api_key' => 'sk-test-secret-value',
            'key_suffix' => 'alue',
            'consented_at' => now(),
        ]);

        $this->assertSame($owner->id, $assistantKey->user_id);
    }
}
