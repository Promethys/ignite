# Configuration

Ignite is configured entirely through environment variables, read from a `.env` file (copied from `.env.example`, see [Installation](/installation)). This page is a grouped reference of the variables that matter for running the app.

## Core

| Variable | What it does | Default / example |
| --- | --- | --- |
| `APP_NAME` | Display name for the app, used in mail, page titles, and vite build. | `Ignite` |
| `APP_ENV` | Runtime environment (`local`, `production`, etc). | `local` |
| `APP_KEY` | Encryption key used for sessions, cookies, and encrypted data. Generate with `php artisan key:generate`. | empty until generated |
| `APP_URL` | Base URL the app is served from. | `http://localhost` |
| `APP_LOCALE` | Default application locale. | `en` |
| `APP_FALLBACK_LOCALE` | Locale used when a translation is missing for `APP_LOCALE`. | `en` |

## Database

| Variable | What it does | Default / example |
| --- | --- | --- |
| `DB_CONNECTION` | Database driver Laravel connects with. | `pgsql` |
| `DB_HOST` | Database host. | `127.0.0.1` |
| `DB_PORT` | Database port. | `5432` |
| `DB_DATABASE` | Database name. | `ignite` |
| `DB_USERNAME` | Database user. | `postgres` |
| `DB_PASSWORD` | Database password. | `secret` |

PostgreSQL is the app's default database, both locally and in production. SQLite is used only by the automated test suite, not for local development.

## Session, cache, and queue

| Variable | What it does | Default / example |
| --- | --- | --- |
| `SESSION_DRIVER` | Where session data is stored. | `database` |
| `CACHE_STORE` | Where cached data is stored. | `database` |
| `QUEUE_CONNECTION` | How queued jobs are dispatched and processed. | `database` |

Locally, queued jobs are stored in the `jobs` database table and processed by a worker, `php artisan queue:listen` (part of `composer dev`, see [Installation](/installation)). Production overrides `QUEUE_CONNECTION` to `sync`, which runs queued jobs immediately, in-request, so a minimal deployment doesn't need a separate worker process.

## Mail

| Variable | What it does | Default / example |
| --- | --- | --- |
| `MAIL_MAILER` | Mail transport driver. | `log` |
| `MAIL_HOST` | SMTP host (only used by SMTP-based mailers). | `127.0.0.1` |
| `MAIL_PORT` | SMTP port. | `2525` |
| `MAIL_SCHEME` | Connection scheme for the SMTP mailer: `smtp` for STARTTLS (port 587), `smtps` for implicit TLS (port 465). Leave unset to let Symfony infer it from the port. | `null` |
| `MAIL_USERNAME` | SMTP username. | `null` |
| `MAIL_PASSWORD` | SMTP password. | `null` |
| `MAIL_FROM_ADDRESS` | From address on outgoing mail. | `hello@example.com` |
| `MAIL_FROM_NAME` | From name on outgoing mail. | `${APP_NAME}` |
| `MAIL_REPLY_TO` | Address applied as `Reply-To` on every outgoing message, so replies reach a monitored inbox instead of the no-reply From address. Leave unset to send no `Reply-To` header. | unset by default |
| `MAIL_TIMEOUT` | Seconds the SMTP mailer waits on a stalled connection before giving up. Unset means PHP's own `default_socket_timeout` applies, which is usually 60. | `null` |

In development, `MAIL_MAILER=log` writes outgoing mail to `storage/logs/laravel.log` instead of sending it, so no SMTP credentials are required to try the app locally.

`MAIL_ENCRYPTION` does nothing on this version of Laravel. It was replaced by `MAIL_SCHEME`, and many provider guides still show the old variable. Setting it is silently ignored.

Ignite only sends two emails, both tied to authentication: email verification and password reset. Both are queued notifications, which matters if `QUEUE_CONNECTION` is not `sync`. See [Self-Hosting](/self-hosting) for the consequences.

Set `MAIL_TIMEOUT` to something short, around 10 seconds, whenever `QUEUE_CONNECTION=sync`. Mail is then sent inside the web request, so an unresponsive SMTP server would otherwise hold a registration or password-reset request open for the full socket timeout.

### Sending real email

Ignite uses Laravel's stock mailers and ships no mail-provider package, so any SMTP service works with the standard variables:

```ini
MAIL_MAILER=smtp
MAIL_HOST=smtp.your-provider.example
MAIL_PORT=587
MAIL_SCHEME=smtp
MAIL_USERNAME=your-username
MAIL_PASSWORD=your-password
MAIL_FROM_ADDRESS=no-reply@your-domain
MAIL_REPLY_TO=contact@your-domain
```

Most transactional providers issue an API key that doubles as the SMTP password, and require the `MAIL_FROM_ADDRESS` domain to be verified in their dashboard before they will accept mail. Check your provider's own SMTP guide for the host, port, and username to use.

## Logging

| Variable | What it does | Default / example |
| --- | --- | --- |
| `LOG_CHANNEL` | Channel used for all application logging. | `stack` |
| `LOG_STACK` | Channels the `stack` channel writes to. | `daily` |
| `LOG_LEVEL` | Minimum severity that gets recorded. | `debug` |

The default writes rotating files under `storage/logs`, which suits a VPS or bare-metal install.

**On any container platform, set `LOG_CHANNEL=stderr` instead.** Railway, Docker, Fly, Render and Kubernetes all collect logs from the container's standard output and standard error, and none of them read files out of the filesystem. Left on the file default, logs are invisible to the platform's log viewer and are discarded whenever the container is replaced, which happens on every deploy.

This matters more than it looks. When an email fails to send, Ignite deliberately keeps the request working and records the failure in the log instead of surfacing an error, so that log line is the only evidence the failure happened.

## Feature gates

| Variable | What it does | Default / example |
| --- | --- | --- |
| `VERIFY_EMAIL` | Toggles the email-verification wall on registration and login. Production runs with it enabled. | `false` in `.env.example` for local dev; the app's own config default is `true` when unset, so production keeps verification enforced unless explicitly disabled. Requires working mail, since it gates every authenticated route. See the authentication feature page. |
| `FORMBRICKS_WORKSPACE_ID` | Workspace ID for the Formbricks feedback survey integration. | unset by default (feature disabled) |
| `FORMBRICKS_APP_URL` | Base URL of the Formbricks instance. | `https://app.formbricks.com` |
| `FORMBRICKS_WEBHOOK_SECRET` | Secret used to verify incoming Formbricks webhooks. | unset by default |
| `DISCORD_OPS_ENABLED` | Toggles sending operational notifications to a Discord webhook. | `false` |
| `DISCORD_OPS_WEBHOOK_URL` | Discord webhook URL that receives operational notifications when enabled. | unset by default |
| `MCP_LOCAL_USER` | Email address of the user the MCP server acts as over the local stdio transport. Leave unset to expose no tools over stdio; the HTTP transport is unaffected either way. | unset by default. See the MCP server feature page |

Each of these gates a feature that is off by default until its variable is set. See the corresponding feature page for setup details on the Formbricks survey and Discord operational notifications.
