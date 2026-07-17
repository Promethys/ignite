<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PasswordConfirmationTest extends TestCase
{
    use RefreshDatabase;

    public function test_confirm_password_screen_can_be_rendered()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('password.confirm'));

        $response->assertStatus(200);

        $response->assertInertia(fn (Assert $page) => $page
            ->component('auth/ConfirmPassword')
        );
    }

    public function test_password_confirmation_requires_authentication()
    {
        $response = $this->get(route('password.confirm'));

        $response->assertRedirect(route('login'));
    }

    public function test_confirming_password_for_the_admin_panel_forces_a_full_page_visit(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->withSession(['url.intended' => url('/admin')])
            ->withHeader('X-Inertia', 'true')
            ->post(route('password.confirm.store'), ['password' => 'password']);

        $response->assertStatus(409);
        $response->assertHeader('X-Inertia-Location', url('/admin'));
    }

    public function test_confirming_password_for_a_non_admin_destination_redirects_normally(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->withSession(['url.intended' => url('/dashboard')])
            ->withHeader('X-Inertia', 'true')
            ->post(route('password.confirm.store'), ['password' => 'password']);

        $response->assertRedirect(url('/dashboard'));
        $response->assertHeaderMissing('X-Inertia-Location');
    }
}
