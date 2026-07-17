<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Fortify\Features;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered()
    {
        $response = $this->get(route('login'));

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen()
    {
        $user = User::factory()->create();

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false))
            ->assertInertiaFlash('toast.type', 'success')
            ->assertInertiaFlash('toast.message', 'Welcome back!');
    }

    public function test_users_with_two_factor_enabled_are_redirected_to_two_factor_challenge()
    {
        if (! Features::canManageTwoFactorAuthentication()) {
            $this->markTestSkipped('Two-factor authentication is not enabled.');
        }

        Features::twoFactorAuthentication([
            'confirm' => true,
            'confirmPassword' => true,
        ]);

        $user = User::factory()->create();

        $user->forceFill([
            'two_factor_secret' => encrypt('test-secret'),
            'two_factor_recovery_codes' => encrypt(json_encode(['code1', 'code2'])),
            'two_factor_confirmed_at' => now(),
        ])->save();

        $response = $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('two-factor.login'));
        $response->assertSessionHas('login.id', $user->id);
        $this->assertGuest();
    }

    public function test_users_can_not_authenticate_with_invalid_password()
    {
        $user = User::factory()->create();

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('logout'));

        $this->assertGuest();
        $response->assertRedirect(route('home'))
            ->assertInertiaFlash('toast.type', 'success')
            ->assertInertiaFlash('toast.message', 'You have been signed out.');
    }

    public function test_users_are_rate_limited()
    {
        $user = User::factory()->create();

        RateLimiter::increment(implode('|', [$user->email, '127.0.0.1']), amount: 10);

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('email');

        $errors = session('errors');

        $this->assertStringContainsString('Too many login attempts', $errors->first('email'));
    }

    public function test_login_adopts_a_valid_guest_locale_cookie()
    {
        $user = User::factory()->create(['locale' => 'en']);

        $this->withUnencryptedCookie('locale', 'fr')->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertSame('fr', $user->fresh()->locale);
    }

    public function test_login_ignores_an_unsupported_locale_cookie()
    {
        $user = User::factory()->create(['locale' => 'en']);

        $this->withUnencryptedCookie('locale', 'de')->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertSame('en', $user->fresh()->locale);
    }

    public function test_login_keeps_the_saved_locale_without_a_cookie()
    {
        $user = User::factory()->create(['locale' => 'fr']);

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertSame('fr', $user->fresh()->locale);
    }

    public function test_login_clears_the_locale_cookie_after_adopting_it()
    {
        $user = User::factory()->create(['locale' => 'en']);

        $response = $this->withUnencryptedCookie('locale', 'fr')->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertCookieExpired('locale');
    }

    public function test_two_factor_login_still_adopts_the_guest_locale_cookie()
    {
        if (! Features::canManageTwoFactorAuthentication()) {
            $this->markTestSkipped('Two-factor authentication is not enabled.');
        }

        Features::twoFactorAuthentication([
            'confirm' => true,
            'confirmPassword' => true,
        ]);

        $user = User::factory()->create(['locale' => 'en']);

        $user->forceFill([
            'two_factor_secret' => encrypt('test-secret'),
            'two_factor_recovery_codes' => encrypt(json_encode(['code1', 'code2'])),
            'two_factor_confirmed_at' => now(),
        ])->save();

        $response = $this->withUnencryptedCookie('locale', 'fr')->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('two-factor.login'));
        $this->assertSame('fr', $user->fresh()->locale);
    }
}
