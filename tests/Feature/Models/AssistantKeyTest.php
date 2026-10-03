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

    public function test_a_user_cannot_have_two_assistant_keys()
    {
        $user = User::factory()->create();
        AssistantKey::factory()->create(['user_id' => $user->id]);

        $this->expectException(QueryException::class);

        AssistantKey::factory()->create(['user_id' => $user->id]);
    }

    public function test_the_assistant_key_is_deleted_with_its_user()
    {
        $assistantKey = AssistantKey::factory()->create();

        $assistantKey->user->delete();

        $this->assertDatabaseMissing('assistant_keys', ['id' => $assistantKey->id]);
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

        $assistantKey = $owner->assistantKey()->create([
            'user_id' => $other->id,
            'provider' => AssistantProvider::OpenAi,
            'api_key' => 'sk-test-secret-value',
            'key_suffix' => 'alue',
            'consented_at' => now(),
        ]);

        $this->assertSame($owner->id, $assistantKey->user_id);
    }
}
