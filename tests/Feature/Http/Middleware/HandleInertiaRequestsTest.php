<?php

namespace Tests\Feature\Http\Middleware;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class HandleInertiaRequestsTest extends TestCase
{
    use RefreshDatabase;

    public function test_locale_is_shared_with_inertia()
    {
        $user = User::factory()->create(['locale' => 'fr']);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertInertia(fn (AssertableJson $page) => $page
                ->where('locale', 'fr')
                ->has('supportedLocales', 2)
                ->where('supportedLocales.en', 'English')
                ->where('supportedLocales.fr', 'Français')
            );
    }

    public function test_supported_locales_match_config()
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertInertia(fn (AssertableJson $page) => $page
                ->where('supportedLocales', config('locales.supported'))
            );
    }

    public function test_support_email_is_shared_with_inertia()
    {
        config(['app.support_email' => 'help@example.test']);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertInertia(fn (AssertableJson $page) => $page
                ->where('supportEmail', 'help@example.test')
            );
    }
}
