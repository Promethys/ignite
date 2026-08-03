# Authentication

## What it is

Ignite's authentication is built on Laravel Fortify, used headlessly: Fortify handles the backend routes, session logic, and password rules, while the login, registration, password-reset, and two-factor pages are custom Vue components under `resources/js/pages/auth/`. Fortify's own view scaffolding is disabled (`'views' => true` in `config/fortify.php` still registers Fortify's routes, but the app supplies its own Inertia views for them).

Two-factor authentication is enabled in `config/fortify.php` via `Features::twoFactorAuthentication(['confirm' => true, 'confirmPassword' => true])`. Registration, password resets, and profile/password updates are handled by Ignite's own controllers rather than Fortify's built-in feature actions.

## Prerequisites

None for the default local setup. Optional: a working mail service if you plan to turn on email verification (see below), since verification relies on sending a link. Production runs with verification enabled.

## The env var: `VERIFY_EMAIL`

Email verification is gated by a single variable:

```ini
VERIFY_EMAIL=false
```

This is the default in `.env.example`. It's read in `config/auth.php` as `'verify_email' => (bool) env('VERIFY_EMAIL', true)`.

::: warning The default flips depending on where you look
`.env.example` ships it `false` for local dev, but if the variable is absent entirely (unset, not just empty), the code-level default is `true`. A deployment that never sets it therefore enforces verification.
:::

Behavior:

- **`VERIFY_EMAIL=false`** (local dev default): `RegisteredUserController::store` calls `$user->markEmailAsVerified()` immediately on registration, so the verification wall never appears.
- **`VERIFY_EMAIL=true`**: newly registered users are left unverified and must confirm their email via the standard Fortify verification flow before accessing verified-only routes.

Turn it on once a real mail service is configured (see [Configuration](/configuration) for the `MAIL_*` variables). With the default `MAIL_MAILER=log`, a verification email is only ever written to `storage/logs/laravel.log`, so no real user could ever click the link.

### What the wall actually covers

The `verified` middleware is applied to the whole authenticated app: the dashboard, goals, categories, and settings. An unverified user can sign in, but every one of those routes redirects them back to the verification prompt, so in practice the account is unusable until the link is clicked.

The API surface is deliberately not gated. Sanctum-authenticated routes and the MCP server do not carry the `verified` middleware, so an integration keeps working regardless of verification state.

Both the verify link and the resend endpoint are rate limited to six requests per minute.

## The emails themselves

Verification and password reset are the only two emails Ignite sends. Both are queued notifications that extend Laravel's own, keeping the framework's signed-URL and token generation untouched while replacing the copy and the styling:

- They are written in the recipient's own language. `User` exposes its stored `locale` as a locale preference, so Laravel renders each notification in that locale, including the framework's own boilerplate.
- They use a custom markdown mail theme in Ignite's brand colours, at `resources/views/vendor/mail/html/themes/ignite.css`.
- Every message carries a `Reply-To` pointing at `MAIL_REPLY_TO`, so replies to a no-reply address still reach a real inbox.

### Failures never break the flow

If the mail transport throws, Ignite catches it, logs it, and lets the request finish. Registration still creates the account and signs the user in; a password-reset request still returns its usual neutral response, which deliberately reveals nothing about whether the address exists. Only the resend button reports the failure directly, telling the user the link could not be sent and inviting them to try again.

::: warning
The consequence is that **a failed send is visible only in the log**. On a container platform, that means `LOG_CHANNEL` must point at `stderr` or the evidence is lost. Failures are recorded with a `category` of `auth`.
:::

## How to verify

- Register a new account locally with `VERIFY_EMAIL=false`: you should land straight on the dashboard with no verification prompt.
- Check `email_verified_at` on the created user row; it should be set immediately.
- With `VERIFY_EMAIL=true` and a working mailer, registering should leave `email_verified_at` null until the emailed link is clicked.
- Set your account's language to French and trigger a password reset: the subject, body, sign-off, and the "trouble clicking the button" footer should all be in French.
- Point `MAIL_HOST` at an unreachable host and register: the account should still be created and you should still land on the app, with the failure recorded in the log.

See [Configuration](/configuration) for the full environment variable reference.
