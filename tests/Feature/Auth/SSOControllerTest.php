<?php

namespace Tests\Feature\Auth;

use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

class SSOControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_redirect_rejects_an_unsupported_provider()
    {
        $this->get(route('sso.redirect', ['provider' => 'twitter']))
            ->assertForbidden();
    }

    public function test_redirect_delegates_to_socialite()
    {
        Socialite::fake('google');

        $this->get(route('sso.redirect', ['provider' => 'google']))
            ->assertRedirect();
    }

    public function test_callback_rejects_an_unsupported_provider()
    {
        $this->get(route('sso.callback', ['provider' => 'twitter']))
            ->assertForbidden();
    }

    public function test_callback_logs_in_and_redirects_to_the_dashboard()
    {
        $user = User::factory()->create();
        SocialAccount::factory()->for($user)->create([
            'provider' => 'google',
            'provider_id' => '12345',
        ]);

        Socialite::fake('google', $this->socialiteUser());

        $this->get(route('sso.callback', ['provider' => 'google']))
            ->assertRedirect(route('dashboard', absolute: false))
            ->assertInertiaFlash('toast.type', 'success');

        $this->assertAuthenticatedAs($user);
    }

    public function test_callback_registers_an_unknown_visitor_and_logs_them_in()
    {
        Socialite::fake('google', $this->socialiteUser());

        $this->get(route('sso.callback', ['provider' => 'google']))
            ->assertRedirect(route('dashboard', absolute: false));

        $user = User::where('email', 'jane@example.com')->sole();

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->email_verified_at);
        $this->assertDatabaseHas('social_accounts', [
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_id' => '12345',
        ]);
    }

    public function test_callback_redirects_to_login_when_the_provider_email_is_unverified()
    {
        User::factory()->create(['email' => 'jane@example.com']);

        Socialite::fake('google', $this->socialiteUser(['verified_email' => false]));

        $this->get(route('sso.callback', ['provider' => 'google']))
            ->assertRedirect(route('login'))
            ->assertInertiaFlash('toast.type', 'error');

        $this->assertGuest();
    }

    public function test_callback_rejects_when_the_provider_throws()
    {
        $driver = Mockery::mock();
        $driver->shouldReceive('user')->andThrow(new \RuntimeException('provider down'));
        Socialite::shouldReceive('driver')->with('google')->andReturn($driver);

        $this->get(route('sso.callback', ['provider' => 'google']))
            ->assertRedirect(route('login'))
            ->assertInertiaFlash('toast.type', 'error');

        $this->assertGuest();
    }

    public function test_callback_regenerates_the_session_so_a_fixed_id_cannot_survive_login()
    {
        $user = User::factory()->create();
        SocialAccount::factory()->for($user)->create([
            'provider' => 'google',
            'provider_id' => '12345',
        ]);

        Socialite::fake('google', $this->socialiteUser());

        $this->startSession();
        $idBeforeLogin = session()->getId();

        $this->get(route('sso.callback', ['provider' => 'google']));

        $this->assertNotSame($idBeforeLogin, session()->getId());
        $this->assertAuthenticatedAs($user);
    }

    public function test_an_authenticated_visitor_cannot_re_enter_the_callback()
    {
        $user = User::factory()->create();

        Socialite::fake('google', $this->socialiteUser());

        $this->actingAs($user)
            ->get(route('sso.callback', ['provider' => 'google']))
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertDatabaseCount('social_accounts', 0);
    }

    public function test_callback_challenges_a_user_with_two_factor_enabled_instead_of_logging_them_in()
    {
        $user = User::factory()->withTwoFactor()->create();
        SocialAccount::factory()->for($user)->create([
            'provider' => 'google',
            'provider_id' => '12345',
        ]);

        Socialite::fake('google', $this->socialiteUser());

        $this->get(route('sso.callback', ['provider' => 'google']))
            ->assertRedirect(route('two-factor.login'));

        $this->assertGuest();
    }

    public function test_callback_hands_the_challenge_the_session_keys_fortify_reads()
    {
        $user = User::factory()->withTwoFactor()->create();
        SocialAccount::factory()->for($user)->create([
            'provider' => 'google',
            'provider_id' => '12345',
        ]);

        Socialite::fake('google', $this->socialiteUser());

        $this->get(route('sso.callback', ['provider' => 'google']))
            ->assertSessionHas('login.id', $user->id)
            ->assertSessionHas('login.remember', false);
    }

    public function test_callback_links_the_account_even_though_the_login_is_deferred_to_the_challenge()
    {
        $user = User::factory()->withTwoFactor()->create(['email' => 'jane@example.com']);

        Socialite::fake('google', $this->socialiteUser());

        $this->get(route('sso.callback', ['provider' => 'google']))
            ->assertRedirect(route('two-factor.login'));

        $this->assertDatabaseHas('social_accounts', [
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_id' => '12345',
        ]);
        $this->assertGuest();
    }

    public function test_callback_does_not_challenge_a_user_who_never_confirmed_the_two_factor_setup()
    {
        $user = User::factory()->withTwoFactor()->create([
            'two_factor_confirmed_at' => null,
        ]);
        SocialAccount::factory()->for($user)->create([
            'provider' => 'google',
            'provider_id' => '12345',
        ]);

        Socialite::fake('google', $this->socialiteUser());

        $this->get(route('sso.callback', ['provider' => 'google']))
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticatedAs($user);
    }

    public function test_callback_logs_in_normally_when_the_two_factor_feature_is_disabled()
    {
        config(['fortify.features' => []]);

        $user = User::factory()->withTwoFactor()->create();
        SocialAccount::factory()->for($user)->create([
            'provider' => 'google',
            'provider_id' => '12345',
        ]);

        Socialite::fake('google', $this->socialiteUser());

        $this->get(route('sso.callback', ['provider' => 'google']))
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticatedAs($user);
    }

    private function socialiteUser(array $overrides = []): SocialiteUser
    {
        return SocialiteUser::fake(array_merge([
            'id' => '12345',
            'nickname' => 'janedoe',
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'verified_email' => true,
        ], $overrides));
    }
}
