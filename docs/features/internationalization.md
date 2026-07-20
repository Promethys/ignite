# Internationalization

## What it is

Ignite ships with two locales, English (`en`) and French (`fr`), covering both the backend (validation messages, mail, flash toasts) and the frontend (Vue component strings). The supported set is a single source of truth in `config/locales.php`:

```php
'supported' => [
    'en' => 'English',
    'fr' => 'Français',
],
```

## Prerequisites

None. Both shipped locales work out of the box with no configuration.

## The env vars: `APP_LOCALE` / `APP_FALLBACK_LOCALE`

```ini
APP_LOCALE=en
APP_FALLBACK_LOCALE=en
```

Both default to `en` in `.env.example`. `APP_FALLBACK_LOCALE` is the locale `App\Http\Middleware\SetLocale` falls back to when nothing else resolves a supported locale for the request.

## How the locale is resolved

`SetLocale` runs on every request and picks the active locale in this order:

1. The authenticated user's persisted `locale` column, if logged in.
2. A guest `locale` cookie (`App\Support\GuestLocale`), used before registration.
3. The browser's `Accept-Language` header, negotiated against `config('locales.supported')`.
4. `APP_FALLBACK_LOCALE`.

Any resolved value outside the supported list is rejected back to the fallback, so an unsupported `Accept-Language` or a tampered cookie can't select a locale with no translation files.

On the frontend, `resources/js/lib/i18n.ts` boots `laravel-vue-i18n` from the locale the server already resolved (read off the `<html lang>` attribute), so the client and server never disagree on first paint.

## Translation file layout

- `lang/<locale>/*.php`: PHP-array translation files, one per domain (`auth.php`, `goals.php`, `validation.php`, `toasts.php`, and so on). These exist for both `lang/en/` and `lang/fr/`.
- `lang/<locale>.json`: flat string-key translations used by `laravel-vue-i18n`'s `$t()` on the frontend. Only `lang/en.json` exists today; French frontend strings are served from the PHP files only (compiled to `php_fr.json` at build time by the `laravel-vue-i18n` Vite plugin).
- `resources/js/lib/i18n.ts` merges the build-time-generated `php_<locale>.json` with the hand-written `<locale>.json` at runtime, JSON winning on key collisions.

## Adding a new locale

1. Add the locale code and display name to `config/locales.php`'s `supported` array.
2. Create `lang/<locale>/` with the same PHP files as `lang/en/`, translated.
3. Optionally add `lang/<locale>.json` for any frontend-only strings not covered by the PHP files.
4. Rebuild frontend assets (`npm run build` or restart `npm run dev`) so the Vite plugin picks up the new `php_<locale>.json`.

## How to verify

- Switch locale via the in-app language switcher (`LanguageSwitcher.vue` / `LocaleSelect.vue`) and confirm both page chrome and validation error text change.
- Send a request with `Accept-Language: fr` and no session/cookie locale; the response should render in French.
- Check a logged-in user's `locale` column takes priority over both the cookie and the header.

See [Configuration](/configuration) for the full environment variable reference.
