<?php

namespace Tests\Unit\Support;

use App\Support\GuestLocale;
use Illuminate\Http\Request;
use Tests\TestCase;

class GuestLocaleTest extends TestCase
{
    public function test_supported_accepts_configured_locales()
    {
        $this->assertTrue(GuestLocale::supported('en'));
        $this->assertTrue(GuestLocale::supported('fr'));
    }

    public function test_supported_rejects_unknown_and_null_locales()
    {
        $this->assertFalse(GuestLocale::supported('de'));
        $this->assertFalse(GuestLocale::supported(null));
    }

    public function test_from_request_returns_a_supported_cookie()
    {
        $request = Request::create('/', 'GET', cookies: ['locale' => 'fr']);

        $this->assertSame('fr', GuestLocale::fromRequest($request));
    }

    public function test_from_request_returns_null_for_an_unsupported_cookie()
    {
        $request = Request::create('/', 'GET', cookies: ['locale' => 'de']);

        $this->assertNull(GuestLocale::fromRequest($request));
    }

    public function test_from_request_returns_null_without_a_cookie()
    {
        $request = Request::create('/', 'GET');

        $this->assertNull(GuestLocale::fromRequest($request));
    }
}
