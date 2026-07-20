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
| `MAIL_USERNAME` | SMTP username. | `null` |
| `MAIL_PASSWORD` | SMTP password. | `null` |
| `MAIL_FROM_ADDRESS` | From address on outgoing mail. | `hello@example.com` |
| `MAIL_FROM_NAME` | From name on outgoing mail. | `${APP_NAME}` |

In development, `MAIL_MAILER=log` writes outgoing mail to `storage/logs/laravel.log` instead of sending it, so no SMTP credentials are required to try the app locally.

## Feature gates

| Variable | What it does | Default / example |
| --- | --- | --- |
| `VERIFY_EMAIL` | Toggles the email-verification wall on registration and login. | `false` in `.env.example` for local dev; the app's own config default is `true` when unset, so production keeps verification enforced unless explicitly disabled. See the authentication feature page. |
| `FORMBRICKS_WORKSPACE_ID` | Workspace ID for the Formbricks feedback survey integration. | unset by default (feature disabled) |
| `FORMBRICKS_APP_URL` | Base URL of the Formbricks instance. | `https://app.formbricks.com` |
| `FORMBRICKS_WEBHOOK_SECRET` | Secret used to verify incoming Formbricks webhooks. | unset by default |
| `DISCORD_OPS_ENABLED` | Toggles sending operational notifications to a Discord webhook. | `false` |
| `DISCORD_OPS_WEBHOOK_URL` | Discord webhook URL that receives operational notifications when enabled. | unset by default |

Each of these gates a feature that is off by default until its variable is set. See the corresponding feature page for setup details on the Formbricks survey and Discord operational notifications.
