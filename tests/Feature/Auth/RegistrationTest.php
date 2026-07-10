<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
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
            'password' => 'password',
            'password_confirmation' => 'password',
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
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'french@example.com',
            'locale' => 'fr',
        ]);
    }

    public function test_new_user_defaults_to_english_without_locale_cookie()
    {
        $this->post(route('register.store'), [
            'name' => 'No Cookie User',
            'email' => 'nocookie@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
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
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'german@example.com',
            'locale' => 'en',
        ]);
    }
}
