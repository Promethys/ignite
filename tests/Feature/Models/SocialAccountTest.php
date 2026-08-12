<?php

namespace Tests\Feature\Models;

use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SocialAccountTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // RELATIONSHIP TESTS
    // =========================================================================

    public function test_social_account_belongs_to_user()
    {
        $user = User::factory()->create();
        $account = SocialAccount::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $account->user);
        $this->assertEquals($user->id, $account->user_id);
    }

    // =========================================================================
    // FILLABLE TESTS
    // =========================================================================

    public function test_provider_and_provider_id_are_mass_assignable()
    {
        $user = User::factory()->create();

        $account = SocialAccount::create([
            'user_id' => $user->id,
            'provider' => 'github',
            'provider_id' => '67890',
            'token' => 'access-token',
            'refresh_token' => 'refresh-token',
            'provider_data' => ['avatar_url' => 'https://cdn.test/v.png'],
        ]);

        $this->assertEquals('github', $account->provider);
        $this->assertEquals('67890', $account->provider_id);
        $this->assertEquals('access-token', $account->token);
        $this->assertEquals('refresh-token', $account->refresh_token);
    }

    public function test_provider_data_is_cast_to_and_from_an_array()
    {
        $account = SocialAccount::factory()->create([
            'provider_data' => ['name' => 'Jane', 'avatar' => 'https://cdn.test/j.png'],
        ]);

        $reloaded = $account->fresh();

        $this->assertIsArray($reloaded->provider_data);
        $this->assertSame('Jane', $reloaded->provider_data['name']);
        $this->assertSame('https://cdn.test/j.png', $reloaded->provider_data['avatar']);
    }

    public function test_provider_and_provider_id_are_unique_together()
    {
        SocialAccount::factory()->create([
            'provider' => 'google',
            'provider_id' => '1',
        ]);

        $this->expectException(QueryException::class);

        SocialAccount::factory()->create([
            'provider' => 'google',
            'provider_id' => '1',
        ]);
    }
}
