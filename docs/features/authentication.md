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

## Social login (Google and GitHub)

Sign-in with Google and GitHub is built on [Laravel Socialite](https://laravel.com/docs/socialite) and runs alongside Fortify. Email and password always remain available, so SSO is an addition, never a replacement. A user can link both providers to the same account.

SSO is **optional and entirely env-driven**. The provider buttons on the login and register pages render only when credentials are configured, so a self-hoster who brings no OAuth credentials sees no broken buttons and the app behaves exactly as it did before.

### Setup

Register an OAuth application at each provider, then set its credentials:

```ini
GITHUB_CLIENT_ID=your-github-client-id
GITHUB_CLIENT_SECRET=your-github-client-secret

GOOGLE_CLIENT_ID=your-google-client-id
GOOGLE_CLIENT_SECRET=your-google-client-secret
```

The redirect URL registered at each provider must match the app's callback path:

- GitHub: `https://your-domain/auth/github/callback`
- Google: `https://your-domain/auth/google/callback`

A provider's button appears the moment its `*_CLIENT_ID` is set. No restart or extra flag is needed.

### The data model

Linked providers live in a dedicated `social_accounts` table (`user_id`, `provider`, `provider_id`, and the full raw `provider_data` payload), **not** as columns on `users`. Ignite does not store OAuth access or refresh tokens: it never calls a provider API on the user's behalf, so keeping them would be a liability with no use. The raw payload preserves what the provider returns at sign-in (avatar, nickname, profile fields) and is cast to an array on read. The supported providers are declared in `config/auth.php` under `auth.sso.supported`, with display names under `auth.sso.labels`.

A unique index on `(provider, provider_id)` means one provider identity can belong to exactly one Ignite account. That constraint, not just controller logic, is what makes the linking rules below hold.

### Account linking and takeover protection

When a provider callback arrives for a visitor who is **not** signed in, Ignite resolves the identity in this order:

1. An existing `social_accounts` link for that provider and provider id logs the owning user straight in.
2. Otherwise, if a user already owns that email **and the provider reports the email as verified**, the provider is linked to that user and they are logged in.
3. If the email exists but the provider did not confirm it as verified, the login is rejected. Ignite never blind-links an unverified email, because a provider that does not verify email cannot prove the person controls the address.
4. No existing user matches: a new account is created with no password and a pre-verified email, the provider is linked, and the standard `Registered` event fires.

How step 2 decides an email is verified depends on the provider, via `all_emails_verified` in `config/services.php`:

- **Google** returns a `verified_email` flag in its payload, which is read directly.
- **GitHub** exposes no such flag. Socialite fetches the address from GitHub's `/user/emails` endpoint and returns only the one that is both primary and verified, so any email that arrives at all is already verified. GitHub is therefore marked `all_emails_verified`.

### Two-factor and social login

Signing in with a provider goes through the same two-factor challenge as signing in with a password. If the account has two-factor enabled and confirmed, the callback does not log the user in; it hands off to Fortify's challenge screen, and the session is only established once the code is accepted. A linked provider is not a way around the second factor.

### Managing connected accounts

Signed-in users manage their providers under **Settings → Connected accounts**. The page lists every supported provider, connected or not, showing the provider account's email and when it was linked, so it is clear _which_ Google or GitHub account is attached.

Connecting from this page is a different operation from signing in, and the callback handles it differently:

- The identity always attaches to the account that is currently signed in, never to whoever happens to own the matching email.
- If the provider identity already belongs to a different Ignite account, the request is refused. The link is never reassigned.
- The provider email does not have to match the account's own email, and it does not need to be verified. Verification exists to stop someone claiming an identity by asserting an email; when connecting, the account holder is already authenticated.
- Connecting never overwrites the account's name or email.

Disconnecting is allowed as long as the account keeps a way to sign in: it needs either a password or another linked provider. A provider that is the only remaining credential cannot be disconnected, and the page offers a link to create a password instead of a dead button. The server enforces this independently of the interface.

The page and its navigation entry appear only when at least one provider is configured.

### Password-less users

An account created through SSO has no password, which is why the `users.password` column is nullable. Such an account signs in through its linked provider.

These users can add a password at any time from **Settings → Password**. The page adapts: with no password on the account it asks only for the new one and its confirmation, since there is no current password to prove. The new password still has to meet the same strength rules, and creating one does not unlink the provider. Both ways in then work.

::: tip Two-factor and the password gate
Fortify's password-confirmation screens (used for 2FA setup and account deletion) assume a password exists. An account that has never set one cannot clear those gates, so it must create a password before enabling two-factor authentication.
:::

If a provider returns an error mid-flow, Ignite logs it (with a `category` of `auth`) and redirects back to the login page with an error toast, rather than leaving the user on a broken callback.

## How to verify

- Register a new account locally with `VERIFY_EMAIL=false`: you should land straight on the dashboard with no verification prompt.
- Check `email_verified_at` on the created user row; it should be set immediately.
- With `VERIFY_EMAIL=true` and a working mailer, registering should leave `email_verified_at` null until the emailed link is clicked.
- Set your account's language to French and trigger a password reset: the subject, body, sign-off, and the "trouble clicking the button" footer should all be in French.
- Point `MAIL_HOST` at an unreachable host and register: the account should still be created and you should still land on the app, with the failure recorded in the log.
- With both `GITHUB_CLIENT_ID` and `GOOGLE_CLIENT_ID` set, the login and register pages should show both provider buttons; with only one set, only that one appears.
- Click a provider button: after the OAuth round trip you should land on the dashboard, and a `social_accounts` row should exist for your user.
- Register first by email, then sign in via a provider whose email matches and is verified: the provider should link to your existing account (no duplicate user), and the second provider should link to the same account too.
- Enable two-factor, sign out, then sign in with a provider: you should be stopped at the two-factor challenge rather than landing on the dashboard.
- Open **Settings → Connected accounts** and connect the provider you have not linked yet: it should appear with its email and link date, and your account name and email should be unchanged.
- With two providers linked and no password, disconnect one: it should succeed. Disconnect the second: the button should be gone, replaced by a prompt to create a password.
- From a second account, try connecting a provider identity already linked elsewhere: it should be refused, and the original link should survive.

See [Configuration](/configuration) for the full environment variable reference.
