<?php

namespace App\Support;

use Illuminate\Http\Request;

class GuestLocale
{
    public static function fromRequest(Request $request): ?string
    {
        $cookie = self::supported($request->cookie('locale'))
            ? $request->cookie('locale')
            : null;

        return $cookie;
    }

    public static function supported(?string $locale): bool
    {
        $supported = array_keys(config('locales.supported'));

        return in_array($locale, $supported, true);
    }
}
