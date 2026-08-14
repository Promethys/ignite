<?php

namespace Tests\Feature\Settings;

use App\Models\SocialAccount;
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

    public function test_the_page_reports_a_password_and_no_linked_accounts_for_a_plain_user()
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('password.edit'))
            ->assertInertia(fn ($page) => $page
                ->component('settings/Password')
                ->where('hasPassword', true)
                ->where('socialAccounts', [])
            );
    }

    public function test_the_page_reports_the_linked_providers()
    {
        $user = User::factory()->passwordless()->create();
        SocialAccount::factory()->for($user)->create(['provider' => 'google']);
        SocialAccount::factory()->for($user)->create(['provider' => 'github']);

        $this->actingAs($user)
            ->get(route('password.edit'))
            ->assertInertia(fn ($page) => $page
                ->component('settings/Password')
                ->where('hasPassword', false)
                ->where('socialAccounts', ['google', 'github'])
            );
    }

    public function test_a_user_without_a_password_can_create_one_without_supplying_a_current_password()
    {
        $user = User::factory()->passwordless()->create();
        SocialAccount::factory()->for($user)->create(['provider' => 'google']);

        $this->actingAs($user)
            ->from(route('password.edit'))
            ->put(route('password.update'), [
                'password' => 'New-P@ssw0rd123',
                'password_confirmation' => 'New-P@ssw0rd123',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('password.edit'));

        $this->assertTrue(Hash::check('New-P@ssw0rd123', $user->refresh()->password));
    }

    public function test_creating_a_password_keeps_the_linked_account()
    {
        $user = User::factory()->passwordless()->create();
        SocialAccount::factory()->for($user)->create(['provider' => 'google']);

        $this->actingAs($user)
            ->put(route('password.update'), [
                'password' => 'New-P@ssw0rd123',
                'password_confirmation' => 'New-P@ssw0rd123',
            ])
            ->assertSessionHasNoErrors();

        $this->assertTrue($user->refresh()->has_password);
        $this->assertSame(1, $user->socialAccounts()->count());
    }

    public function test_a_user_with_a_password_still_has_to_supply_it()
    {
        $user = User::factory()->create();
        SocialAccount::factory()->for($user)->create(['provider' => 'google']);

        $this->actingAs($user)
            ->from(route('password.edit'))
            ->put(route('password.update'), [
                'password' => 'New-P@ssw0rd123',
                'password_confirmation' => 'New-P@ssw0rd123',
            ])
            ->assertSessionHasErrors('current_password');

        $this->assertFalse(Hash::check('New-P@ssw0rd123', $user->refresh()->password));
    }

    public function test_a_created_password_still_has_to_meet_the_strength_rules()
    {
        $user = User::factory()->passwordless()->create();

        $this->actingAs($user)
            ->from(route('password.edit'))
            ->put(route('password.update'), [
                'password' => 'weak',
                'password_confirmation' => 'weak',
            ])
            ->assertSessionHasErrors('password');

        $this->assertNull($user->refresh()->password);
    }

    public function test_a_created_password_still_has_to_be_confirmed()
    {
        $user = User::factory()->passwordless()->create();

        $this->actingAs($user)
            ->from(route('password.edit'))
            ->put(route('password.update'), [
                'password' => 'New-P@ssw0rd123',
                'password_confirmation' => 'Something-Else123!',
            ])
            ->assertSessionHasErrors('password');

        $this->assertNull($user->refresh()->password);
    }
}
