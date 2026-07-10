<?php

namespace Tests\Feature\Http\Middleware;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class SetLocaleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Register a throwaway route that returns the resolved app locale,
     * so each test can assert the middleware effect through the response.
     */
    protected function setUp(): void
    {
        parent::setUp();

        Route::get('/_test/locale', fn () => response(config('app.locale')))->middleware('web');
    }

    // =========================================================================
    // RESOLUTION ORDER TESTS (one behavior each)
    // =========================================================================

    public function test_authenticated_users_locale_is_applied()
    {
        $user = User::factory()->create(['locale' => 'fr']);

        $response = $this->actingAs($user)->get('/_test/locale');

        $response->assertOk()->assertSee('fr', false);
    }

    public function test_guest_locale_cookie_is_applied()
    {
        $response = $this->withUnencryptedCookie('locale', 'fr')->get('/_test/locale');

        $response->assertOk()->assertSee('fr', false);
    }

    public function test_accept_language_header_is_negotiated_for_guests()
    {
        $response = $this->get('/_test/locale', ['Accept-Language' => 'fr-FR']);

        $response->assertOk()->assertSee('fr', false);
    }

    public function test_unsupported_locale_falls_back_to_english()
    {
        $response = $this->withUnencryptedCookie('locale', 'de')->get('/_test/locale');

        $response->assertOk()->assertSee('en', false);
    }

    public function test_authenticated_locale_takes_precedence_over_cookie()
    {
        $user = User::factory()->create(['locale' => 'fr']);

        $response = $this->actingAs($user)
            ->withUnencryptedCookie('locale', 'en')
            ->get('/_test/locale');

        $response->assertOk()->assertSee('fr', false);
    }
}
