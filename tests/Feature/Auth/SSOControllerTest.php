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
        Socialite::shouldReceive('driver->redirect')
            ->andReturn(redirect('https://provider.test/authorize'));

        $this->get(route('sso.redirect', ['provider' => 'google']))
            ->assertRedirect('https://provider.test/authorize');
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

        $this->mockSocialiteUser('google', $this->socialiteUser());

        $this->get(route('sso.callback', ['provider' => 'google']))
            ->assertRedirect(route('dashboard', absolute: false))
            ->assertInertiaFlash('toast.type', 'success');

        $this->assertAuthenticatedAs($user);
    }

    public function test_callback_redirects_to_login_when_the_provider_email_is_unverified()
    {
        User::factory()->create(['email' => 'jane@example.com']);

        $this->mockSocialiteUser('google', $this->socialiteUser(['verified_email' => false]));

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

    private function socialiteUser(array $overrides = []): SocialiteUser
    {
        $attributes = array_merge([
            'id' => '12345',
            'nickname' => 'janedoe',
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'verified_email' => true,
        ], $overrides);

        $user = (new SocialiteUser)
            ->setRaw($attributes)
            ->map($attributes);

        $user->token = 'fresh-token';
        $user->refreshToken = 'fresh-refresh';

        return $user;
    }

    private function mockSocialiteUser(string $provider, SocialiteUser $user): void
    {
        $driver = Mockery::mock();
        $driver->shouldReceive('user')->andReturn($user);
        Socialite::shouldReceive('driver')->with($provider)->andReturn($driver);
    }
}
