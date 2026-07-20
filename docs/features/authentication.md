# Authentication

## What it is

Ignite's authentication is built on Laravel Fortify, used headlessly: Fortify handles the backend routes, session logic, and password rules, while the login, registration, password-reset, and two-factor pages are custom Vue components under `resources/js/pages/auth/`. Fortify's own view scaffolding is disabled (`'views' => true` in `config/fortify.php` still registers Fortify's routes, but the app supplies its own Inertia views for them).

Two-factor authentication is enabled in `config/fortify.php` via `Features::twoFactorAuthentication(['confirm' => true, 'confirmPassword' => true])`. Registration, password resets, and profile/password updates are handled by Ignite's own controllers rather than Fortify's built-in feature actions.

## Prerequisites

None for the default local setup. Optional: a working mail service if you plan to turn on email verification (see below), since verification relies on sending a link.

## The env var: `VERIFY_EMAIL`

Email verification is gated by a single variable:

```ini
VERIFY_EMAIL=false
```

This is the default in `.env.example`. It's read in `config/auth.php` as `'verify_email' => (bool) env('VERIFY_EMAIL', true)`, so note the asymmetry: `.env.example` ships it `false` for local dev, but if the variable is absent entirely (unset, not just empty), the code-level default is `true`.

Behavior:

- **`VERIFY_EMAIL=false`** (local dev default): `RegisteredUserController::store` calls `$user->markEmailAsVerified()` immediately on registration, so the verification wall never appears.
- **`VERIFY_EMAIL=true`**: newly registered users are left unverified and must confirm their email via the standard Fortify verification flow before accessing verified-only routes.

Turn it on once a real mail service is configured (see [Configuration](/configuration) for the `MAIL_*` variables). With the default `MAIL_MAILER=log`, a verification email is only ever written to `storage/logs/laravel.log`, so no real user could ever click the link.

## How to verify

- Register a new account locally with `VERIFY_EMAIL=false`: you should land straight on the dashboard with no verification prompt.
- Check `email_verified_at` on the created user row; it should be set immediately.
- With `VERIFY_EMAIL=true` and a working mailer, registering should leave `email_verified_at` null until the emailed link is clicked.

See [Configuration](/configuration) for the full environment variable reference.
