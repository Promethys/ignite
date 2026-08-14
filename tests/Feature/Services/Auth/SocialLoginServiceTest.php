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

    public function test_existing_account_returns_its_user_and_refreshes_provider_data()
    {
        $user = User::factory()->create();
        $account = SocialAccount::factory()->for($user)->create([
            'provider' => 'google',
            'provider_id' => '12345',
            'provider_data' => ['avatar' => 'https://cdn.test/stale.png'],
        ]);

        $resolved = $this->service->resolveUser('google', $this->socialiteUser());

        $this->assertTrue($resolved->is($user));
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
        $this->assertFalse($resolved->has_password);
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

    public function test_one_user_can_link_two_different_providers()
    {
        $user = User::factory()->create(['email' => 'jane@example.com']);

        $this->service->resolveUser('google', $this->socialiteUser(['id' => 'google-1']));
        $this->service->resolveUser('github', $this->socialiteUser(['id' => 'github-1']));

        $this->assertSame(2, $user->socialAccounts()->count());
        $this->assertSame(1, User::count());
    }

    public function test_a_second_login_through_the_same_provider_does_not_duplicate_the_link()
    {
        $user = User::factory()->create(['email' => 'jane@example.com']);

        $this->service->resolveUser('google', $this->socialiteUser());
        $this->service->resolveUser('google', $this->socialiteUser());

        $this->assertSame(1, $user->socialAccounts()->count());
    }

    public function test_the_name_falls_back_to_the_nickname_when_the_provider_has_none()
    {
        $this->service->resolveUser('google', $this->socialiteUser(['name' => null]));

        $this->assertDatabaseHas('users', [
            'email' => 'jane@example.com',
            'name' => 'janedoe',
        ]);
    }

    public function test_a_provider_that_does_not_vouch_for_emails_is_rejected_when_the_payload_omits_the_flag()
    {
        config(['services.google.all_emails_verified' => false]);

        $user = User::factory()->create(['email' => 'jane@example.com']);
        $ssoUser = $this->socialiteUser();
        $raw = $ssoUser->getRaw();
        unset($raw['verified_email']);
        $ssoUser->setRaw($raw);

        $this->expectException(UnverifiedSocialEmailException::class);

        try {
            $this->service->resolveUser('google', $ssoUser);
        } finally {
            $this->assertSame(0, $user->socialAccounts()->count());
        }
    }

    public function test_a_provider_that_vouches_for_every_email_links_without_a_payload_flag()
    {
        config(['services.google.all_emails_verified' => true]);

        $user = User::factory()->create(['email' => 'jane@example.com']);
        $ssoUser = $this->socialiteUser();
        $raw = $ssoUser->getRaw();
        unset($raw['verified_email']);
        $ssoUser->setRaw($raw);

        $resolved = $this->service->resolveUser('google', $ssoUser);

        $this->assertTrue($resolved->is($user));
        $this->assertSame(1, $user->socialAccounts()->count());
    }

    public function test_a_failure_after_the_user_is_created_rolls_it_back_rather_than_stranding_it()
    {
        // Registered fires inside createUser, so a listener that throws stands in
        // for anything failing between the user row and its social account. Without
        // the surrounding transaction this leaves an account with no password and
        // no linked provider, which can never be logged into and holds the email.
        Event::listen(Registered::class, function () {
            throw new \RuntimeException('listener exploded');
        });

        $this->expectException(\RuntimeException::class);

        try {
            $this->service->resolveUser('google', $this->socialiteUser());
        } finally {
            $this->assertDatabaseMissing('users', ['email' => 'jane@example.com']);
            $this->assertDatabaseCount('social_accounts', 0);
        }
    }

    public function test_linking_does_not_verify_a_local_account_that_was_never_verified()
    {
        $user = User::factory()->unverified()->create(['email' => 'jane@example.com']);

        $this->service->resolveUser('google', $this->socialiteUser());

        $this->assertNull($user->fresh()->email_verified_at);
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
