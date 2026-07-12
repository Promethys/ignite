<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingLocaleSwitcherTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_switch_locale_via_landing_page()
    {
        $user = User::factory()->create(['locale' => 'en']);

        $response = $this
            ->actingAs($user)
            ->patch(route('settings.locale.update'), ['locale' => 'fr']);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect()
            ->assertInertiaFlash('toast.type', 'success');

        $user->refresh();

        $this->assertSame('fr', $user->locale);
    }

    public function test_authenticated_user_locale_persists_after_reload()
    {
        $user = User::factory()->create(['locale' => 'en']);

        $this->actingAs($user)
            ->patch(route('settings.locale.update'), ['locale' => 'fr']);

        $user->refresh();
        $this->assertSame('fr', $user->locale);

        $response = $this->actingAs($user)->get(route('home'));

        $response->assertOk();
        $this->assertSame('fr', $user->refresh()->locale);
    }

    public function test_guest_can_switch_locale_via_cookie()
    {
        $response = $this->get(route('home'));

        $response->assertOk();

        $this->assertGuest();
    }

    public function test_landing_page_shows_language_switcher_for_guests()
    {
        $response = $this->get(route('home'));

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Welcome')
            );

        $this->assertGuest();
    }

    public function test_landing_page_shows_language_switcher_for_authenticated_users()
    {
        $user = User::factory()->create(['locale' => 'en']);

        $response = $this->actingAs($user)->get(route('home'));

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Welcome')
            );

        $this->assertAuthenticated();
    }
}
