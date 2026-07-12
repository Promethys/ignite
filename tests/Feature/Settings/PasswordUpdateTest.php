<?php

namespace Tests\Feature\Settings;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PasswordUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_update_page_is_displayed()
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('password.edit'));

        $response->assertStatus(200);
    }

    public function test_password_can_be_updated()
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from(route('password.edit'))
            ->put(route('password.update'), [
                'current_password' => 'password',
                'password' => 'New-P@ssw0rd123',
                'password_confirmation' => 'New-P@ssw0rd123',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('password.edit'))
            ->assertInertiaFlash('toast.type', 'success')
            ->assertInertiaFlash('toast.message', 'Password updated.');

        $this->assertTrue(Hash::check('New-P@ssw0rd123', $user->refresh()->password));
    }

    public function test_correct_password_must_be_provided_to_update_password()
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from(route('password.edit'))
            ->put(route('password.update'), [
                'current_password' => 'wrong-password',
                'password' => 'New-P@ssw0rd123',
                'password_confirmation' => 'New-P@ssw0rd123',
            ]);

        $response
            ->assertSessionHasErrors('current_password')
            ->assertRedirect(route('password.edit'));
    }
}
