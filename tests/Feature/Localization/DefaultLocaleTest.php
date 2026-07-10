<?php

namespace Tests\Feature\Localization;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class DefaultLocaleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::get('/_test/default-locale', fn () => response(config('app.locale')))->middleware('web');
    }

    public function test_fallback_locale_is_english_with_no_signals()
    {
        $response = $this->get('/_test/default-locale');

        $response->assertOk()->assertSee('en', false);
    }
}
