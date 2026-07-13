<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered()
    {
        $response = $this->get(route('register'));

        $response->assertStatus(200);
    }

    public function test_new_users_can_register()
    {
        $response = $this->post(route('register.store'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'Strong-P@ssw0rd',
            'password_confirmation' => 'Strong-P@ssw0rd',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false))
            ->assertInertiaFlash('toast.type', 'success')
            ->assertInertiaFlash('toast.message', 'Welcome to Ignite!');
    }

    public function test_guest_locale_cookie_is_seeded_on_new_user()
    {
        $this->withUnencryptedCookie('locale', 'fr');

        $this->post(route('register.store'), [
            'name' => 'French User',
            'email' => 'french@example.com',
            'password' => 'Strong-P@ssw0rd',
            'password_confirmation' => 'Strong-P@ssw0rd',
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'french@example.com',
            'locale' => 'fr',
        ]);
    }

    public function test_registration_clears_the_locale_cookie()
    {
        $response = $this->withUnencryptedCookie('locale', 'fr')->post(route('register.store'), [
            'name' => 'French User',
            'email' => 'french2@example.com',
            'password' => 'Strong-P@ssw0rd',
            'password_confirmation' => 'Strong-P@ssw0rd',
        ]);

        $response->assertCookieExpired('locale');
    }

    public function test_new_user_defaults_to_english_without_locale_cookie()
    {
        $this->post(route('register.store'), [
            'name' => 'No Cookie User',
            'email' => 'nocookie@example.com',
            'password' => 'Strong-P@ssw0rd',
            'password_confirmation' => 'Strong-P@ssw0rd',
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'nocookie@example.com',
            'locale' => 'en',
        ]);
    }

    public function test_unsupported_locale_cookie_falls_back_to_english()
    {
        $this->withUnencryptedCookie('locale', 'de');

        $this->post(route('register.store'), [
            'name' => 'German User',
            'email' => 'german@example.com',
            'password' => 'Strong-P@ssw0rd',
            'password_confirmation' => 'Strong-P@ssw0rd',
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'german@example.com',
            'locale' => 'en',
        ]);
    }

    public function test_new_user_is_unverified_when_email_verification_is_required()
    {
        Notification::fake();

        $this->post(route('register.store'), [
            'name' => 'Unverified User',
            'email' => 'unverified@example.com',
            'password' => 'Strong-P@ssw0rd',
            'password_confirmation' => 'Strong-P@ssw0rd',
        ]);

        $user = User::where('email', 'unverified@example.com')->first();

        $this->assertNotNull($user);
        $this->assertFalse($user->hasVerifiedEmail());
        $this->assertNull($user->email_verified_at);

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_new_user_is_auto_verified_when_email_verification_is_disabled()
    {
        config(['auth.verify_email' => false]);

        Notification::fake();

        $this->post(route('register.store'), [
            'name' => 'Auto Verified User',
            'email' => 'autoverified@example.com',
            'password' => 'Strong-P@ssw0rd',
            'password_confirmation' => 'Strong-P@ssw0rd',
        ]);

        $user = User::where('email', 'autoverified@example.com')->first();

        $this->assertNotNull($user);
        $this->assertTrue($user->hasVerifiedEmail());
        $this->assertNotNull($user->email_verified_at);

        Notification::assertNothingSent();
    }

    public function test_email_verification_is_required_by_default()
    {
        $this->assertTrue(config('auth.verify_email'));
    }

    public function test_registration_rejects_a_weak_password(): void
    {
        $response = $this->from(route('register'))->post(route('register.store'), [
            'name' => 'Weak',
            'email' => 'weak@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertGuest();
    }
}
