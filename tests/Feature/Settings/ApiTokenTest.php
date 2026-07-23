<?php

namespace Tests\Feature\Settings;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiTokenTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_get_redirected_to_login(): void
    {
        $response = $this->actingAsGuest()
            ->get('/settings/api-tokens');

        $response->assertRedirect('/login');
    }

    public function test_guests_cannot_create_an_api_token(): void
    {
        $response = $this->actingAsGuest()
            ->post('/settings/api-tokens');

        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_access_to_the_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get('/settings/api-tokens');

        $response->assertStatus(200);
    }

    public function test_authenticated_user_can_create_an_api_token(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->post('/settings/api-tokens', [
                'name' => 'dummy-token',
                'abilities' => ['write'],
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('personal_access_tokens', [
            'name' => 'dummy-token',
            'tokenable_type' => $user->getMorphClass(),
            'tokenable_id' => $user->getKey(),
            'abilities' => '["read","write"]',
        ]);

        $this->assertCount(1, $user->fresh()->tokens);
    }

    public function test_write_ability_implies_read(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/settings/api-tokens', [
                'name' => 'write-implies-read',
                'abilities' => ['write', 'delete'],
            ])
            ->assertRedirect();

        $token = $user->fresh()->tokens->first();

        $this->assertContains('read', $token->abilities);
        $this->assertContains('write', $token->abilities);
        $this->assertContains('delete', $token->abilities);
    }

    public function test_name_is_required(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/settings/api-tokens', [
                'name' => '',
                'abilities' => ['read'],
            ])
            ->assertSessionHasErrors(['name']);

        $this->assertDatabaseEmpty('personal_access_tokens');
    }

    public function test_abilities_must_be_within_the_allowed_set(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/settings/api-tokens', [
                'name' => 'forbidden-ability',
                'abilities' => ['admin'],
            ])
            ->assertSessionHasErrors(['abilities.0']);

        $this->assertDatabaseEmpty('personal_access_tokens');
    }

    public function test_create_flashes_the_plaintext_token_once(): void
    {
        $user = User::factory()->create();

        // The plaintext token is exposed on the redirected page, inside the
        // one-time `newToken` prop.
        $response = $this->actingAs($user)
            ->from('/settings/api-tokens')
            ->followingRedirects()
            ->post('/settings/api-tokens', [
                'name' => 'revealed-once',
                'abilities' => ['read'],
            ])
            ->assertOk();

        $flashed = $response->inertiaProps('newToken');
        $this->assertIsArray($flashed);
        $this->assertSame('revealed-once', $flashed['name']);
        $this->assertNotEmpty($flashed['token']);

        // Revisiting the page must not re-serve the plaintext token.
        $this->assertNull($this->get('/settings/api-tokens')->inertiaProps('newToken'));
    }

    public function test_index_lists_only_the_users_own_tokens_without_the_secret(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $owner->createToken('mine', ['read']);
        $other->createToken('theirs', ['read']);

        $response = $this->actingAs($owner)
            ->get('/settings/api-tokens')
            ->assertOk();

        $responseProps = $response->inertiaProps();

        $this->assertCount(1, $responseProps['tokens']);

        $token = $responseProps['tokens'][0];
        $this->assertSame('mine', $token['name']);

        // The controller must ship only these columns and never the token
        // hash, the tokenable fields, or anything else sensitive.
        $this->assertSame(
            ['id', 'name', 'abilities', 'last_used_at', 'created_at'],
            array_keys($token),
        );
    }

    public function test_user_can_revoke_their_own_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('revoke-me', ['read']);

        $this->actingAs($user)
            ->from('/settings/api-tokens')
            ->delete("/settings/api-tokens/{$token->accessToken->id}")
            ->assertRedirect('/settings/api-tokens');

        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $token->accessToken->id,
        ]);
    }

    public function test_user_cannot_revoke_another_users_token(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();

        $token = $owner->createToken('owned', ['read']);

        $this->actingAs($intruder)
            ->delete("/settings/api-tokens/{$token->accessToken->id}")
            ->assertNotFound();

        $this->assertDatabaseHas('personal_access_tokens', [
            'id' => $token->accessToken->id,
        ]);
    }
}
