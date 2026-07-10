<?php

namespace Tests\Feature\Settings;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocaleUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_update_locale()
    {
        $user = User::factory()->create(['locale' => 'en']);

        $response = $this->actingAs($user)
            ->patch(route('settings.locale.update'), ['locale' => 'fr']);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect()
            ->assertInertiaFlash('toast.type', 'success');

        $user->refresh();

        $this->assertSame('fr', $user->locale);
    }

    public function test_invalid_locale_is_rejected()
    {
        $user = User::factory()->create(['locale' => 'en']);

        $this->actingAs($user)
            ->patch(route('settings.locale.update'), ['locale' => 'de'])
            ->assertSessionHasErrors('locale');

        $this->assertSame('en', $user->refresh()->locale);
    }

    public function test_guest_is_redirected_to_login()
    {
        $this->patch(route('settings.locale.update'), ['locale' => 'fr'])
            ->assertRedirect(route('login'));
    }
}
