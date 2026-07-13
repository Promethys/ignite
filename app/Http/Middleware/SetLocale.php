<?php

namespace App\Http\Middleware;

use App\Support\GuestLocale;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Resolve the active locale for the request and apply it.
     *
     * Resolution order (first match wins):
     *   1. Authenticated user's persisted locale
     *   2. Guest "locale" cookie
     *   3. Accept-Language header negotiated against supported locales
     *   4. The configured fallback locale
     *
     * Any value outside the supported list is rejected to the fallback.
     * Read-only: this middleware never writes to the database.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $resolved = $this->resolveLocale($request);

        App::setLocale($resolved);

        return $next($request);
    }

    /**
     * Determine the locale to use for this request.
     */
    protected function resolveLocale(Request $request): string
    {
        $supported = array_keys(config('locales.supported', []));
        $fallback = config('app.fallback_locale', 'en');

        $resolved = $request->user()?->locale
            ?? GuestLocale::fromRequest($request)
            ?? $this->negotiateAcceptLanguage($request, $supported)
            ?? $fallback;

        if (! GuestLocale::supported($resolved)) {
            return $fallback;
        }

        return $resolved;
    }

    /**
     * Negotiate the Accept-Language header against the supported locales.
     *
     * Extracts the primary language subtag of each listed language and
     * returns the first that matches a supported locale.
     */
    protected function negotiateAcceptLanguage(Request $request, array $supported): ?string
    {
        $header = $request->headers->get('Accept-Language');

        if ($header === null) {
            return null;
        }

        foreach (explode(',', $header) as $language) {
            // Strip quality values and region subtags: "fr-FR;q=0.9" -> "fr"
            $code = strtolower(trim(explode(';', $language)[0]));
            $primary = trim(explode('-', $code)[0]);

            if (in_array($primary, $supported, true)) {
                return $primary;
            }
        }

        return null;
    }
}
