<?php

namespace Tests\Feature\Services\Auth;

use App\Models\SocialAccount;
use App\Models\User;
use App\Services\Auth\SocialLoginService;
use App\Services\Auth\UnverifiedSocialEmailException;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Laravel\Socialite\Two\User as SocialiteUser;
use Tests\TestCase;

class SocialLoginServiceTest extends TestCase
{
    use RefreshDatabase;

    private SocialLoginService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(SocialLoginService::class);
    }

    public function test_existing_account_returns_its_user_and_syncs_tokens()
    {
        $user = User::factory()->create();
        $account = SocialAccount::factory()->for($user)->create([
            'provider' => 'google',
            'provider_id' => '12345',
            'token' => 'old-token',
            'refresh_token' => 'old-refresh',
        ]);

        $resolved = $this->service->resolveUser('google', $this->socialiteUser());

        $this->assertTrue($resolved->is($user));
        $this->assertSame('fresh-token', $account->fresh()->token);
        $this->assertSame('fresh-refresh', $account->fresh()->refresh_token);
        $this->assertSame('https://cdn.test/jane.png', $account->fresh()->provider_data['avatar']);
    }

    public function test_links_an_existing_user_when_the_provider_email_is_verified()
    {
        $user = User::factory()->create([
            'email' => 'jane@example.com',
            'password' => 'hashed-password',
        ]);

        $this->assertDatabaseMissing('social_accounts', ['user_id' => $user->id]);

        $resolved = $this->service->resolveUser('google', $this->socialiteUser());

        $this->assertTrue($resolved->is($user));
        $this->assertDatabaseHas('social_accounts', [
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_id' => '12345',
        ]);
        $this->assertSame('Jane Doe', $user->socialAccounts->first()->provider_data['name']);
    }

    public function test_links_an_existing_user_via_github_when_the_email_is_verified()
    {
        $user = User::factory()->create(['email' => 'jane@example.com']);

        $resolved = $this->service->resolveUser(
            'github',
            $this->socialiteUser(['verified_email' => null, 'email_verified' => true]),
        );

        $this->assertTrue($resolved->is($user));
        $this->assertDatabaseHas('social_accounts', [
            'user_id' => $user->id,
            'provider' => 'github',
        ]);
    }

    public function test_throws_and_does_not_link_when_the_provider_email_is_unverified()
    {
        $user = User::factory()->create(['email' => 'jane@example.com']);

        $this->expectException(UnverifiedSocialEmailException::class);

        try {
            $this->service->resolveUser('google', $this->socialiteUser(['verified_email' => false]));
        } finally {
            $this->assertDatabaseMissing('social_accounts', ['user_id' => $user->id]);
        }
    }

    public function test_creates_a_new_passwordless_verified_user_with_a_linked_account()
    {
        Event::fake([Registered::class]);

        $this->assertDatabaseMissing('users', ['email' => 'jane@example.com']);

        $resolved = $this->service->resolveUser('google', $this->socialiteUser());

        $this->assertNull($resolved->password);
        $this->assertFalse($resolved->hasPassword());
        $this->assertTrue($resolved->hasVerifiedEmail());

        $this->assertDatabaseHas('social_accounts', [
            'user_id' => $resolved->id,
            'provider' => 'google',
            'provider_id' => '12345',
        ]);

        $this->assertSame('https://cdn.test/jane.png', $resolved->socialAccounts->first()->provider_data['avatar']);

        Event::assertDispatched(Registered::class);
    }

    public function test_new_user_seeds_locale_from_the_argument()
    {
        $this->service->resolveUser('google', $this->socialiteUser(), 'fr');

        $this->assertDatabaseHas('users', [
            'email' => 'jane@example.com',
            'locale' => 'fr',
        ]);
    }

    public function test_new_user_falls_back_to_the_default_locale_when_none_given()
    {
        $this->service->resolveUser('google', $this->socialiteUser());

        $this->assertDatabaseHas('users', [
            'email' => 'jane@example.com',
            'locale' => config('app.fallback_locale'),
        ]);
    }

    private function socialiteUser(array $overrides = []): SocialiteUser
    {
        $attributes = array_merge([
            'id' => '12345',
            'nickname' => 'janedoe',
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'avatar' => 'https://cdn.test/jane.png',
            'verified_email' => true,
        ], $overrides);

        $user = (new SocialiteUser)
            ->setRaw($attributes)
            ->map($attributes);

        $user->token = 'fresh-token';
        $user->refreshToken = 'fresh-refresh';

        return $user;
    }
}
