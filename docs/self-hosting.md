# Self-Hosting

Ignite is source-available (FSL-1.1-MIT) and self-hostable. This page covers running your own instance with the Docker Compose stack shipped in the repo, and then documents the image itself and the deploy shape used by the hosted version, which builds from the same `Dockerfile`.

For local development, including a separate Docker setup with hot reload, see [Installation](/installation).

## Quick start

You need Docker Engine with the Compose v2 plugin. Nothing else: PHP, Node and PostgreSQL all live inside the stack.

### 1. Clone and create your environment file

```bash
git clone https://github.com/Promethys/ignite.git
cd ignite
cp .env.example .env
```

::: warning `.env` has to exist before any Docker command
Compose reads it twice, for two unrelated reasons: `env_file:` injects it into the app containers, and `${DB_DATABASE}`, `${DB_USERNAME}` and `${DB_PASSWORD}` are interpolated into the `postgres` service definition before any container exists. Without the file, Compose fails before it builds anything.

The file is deliberately excluded from the image by `.dockerignore` and never ships inside a container. Compose turns it into real environment variables at run time, the same way a managed platform injects its dashboard variables. An empty `.env` inside a running container is therefore normal.
:::

### 2. Set your own values

At minimum, edit these in `.env`:

```ini
APP_URL=https://ignite.example.com
DB_PASSWORD=<something-other-than-the-default>
```

`.env.example` ships `DB_PASSWORD=secret`. Compose uses it to create the database on first boot, so change it before that first boot rather than after.

Mail is optional. See [Configuration](/configuration) for the `MAIL_*` variables and the [Mail](#mail) section below for what Ignite actually sends.

### 3. Generate the application key

```bash
docker compose run --rm --no-deps --entrypoint php web artisan key:generate --show
```

Copy the printed value into `.env`, unquoted and including the `base64:` prefix:

```ini
APP_KEY=base64:xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx=
```

This is the command that builds the image, so expect it to take a few minutes the first time.

`--show` prints the key instead of writing it. That matters here: unlike the development stack, the production container does not bind-mount your working directory, so a key written inside it would disappear along with the container.

::: danger Generate the key once, then never change it
`APP_KEY` encrypts session cookies and the two-factor secrets and recovery codes stored in the database. Replacing it signs every user out and makes existing two-factor enrolments permanently undecryptable, with no error message saying why.

For the same reason the startup script refuses to generate one for you. A key minted automatically on each boot would look convenient and quietly destroy data on the first container recreate. If `APP_KEY` is missing or empty, the container exits immediately with instructions; read them with `docker compose logs web`.
:::

### 4. Start the stack

```bash
docker compose up -d
```

Compose waits for PostgreSQL to report healthy, runs the migrations and seeders to completion, and only then starts the web container.

### 5. Visit the application

```text
http://localhost:8080
```

Set `APP_PORT` in `.env` to publish a different port. The first account you register becomes an ordinary user; promoting someone to admin is covered in [Admin Panel](/features/admin-panel).

## What the stack contains

| Service | Role | Notes |
| --- | --- | --- |
| `web` | The application, served by FrankenPHP | The only service published to the host |
| `postgres` | PostgreSQL 18 | Deliberately not published; reachable only on the internal network |
| `migrate` | One-shot migration and seed run | Runs to completion before `web` starts, then exits |

Two named volumes hold everything that must survive a rebuild: `postgres-data-production` for the database and `laravel-storage-production` for uploaded and generated files under `storage/`.

Migrations live in their own short-lived service rather than in the web container's startup script. If they ran at startup, a database that was briefly unreachable would turn into a restart loop of the whole app instead of a single failed job.

`web` declares a health check that requests `/up` through PHP, the production image carrying no curl. `docker compose ps` reports whether the application is answering, and other services can depend on it with `condition: service_healthy`.

## Values Compose sets for you

Four variables are pinned in `compose.yaml` and override whatever `.env` says:

| Variable | Forced to | Why |
| --- | --- | --- |
| `APP_ENV` | `production` | `.env.example` ships `local`. Left alone, the seeder would create demo accounts with published credentials on a public instance. |
| `APP_DEBUG` | `false` | Stack traces and environment values must never be rendered to a visitor. |
| `DB_HOST` / `DB_PORT` | `postgres` / `5432` | The database service name on the internal Compose network, not the `127.0.0.1` a native install uses. |
| `LOG_CHANNEL` | `stderr` | Containers surface standard output and error, and read nothing from the filesystem. On the file default, logs are invisible to `docker compose logs` and discarded on every rebuild. |

Everything else in `.env` applies normally. Only these four are ignored if you set them.

## Putting it on the internet

The container speaks plain HTTP and does not obtain certificates. Run a reverse proxy in front of it (Caddy, nginx, Traefik) or expose it through a tunnel, and terminate TLS there.

The application already trusts forwarded headers (`trustProxies(at: '*')` in `bootstrap/app.php`), so it generates correct `https://` URLs and marks the session cookie `Secure` once a proxy sets `X-Forwarded-Proto`. That wildcard is safe only while the container is reachable exclusively through your proxy. Do not also publish its port straight to the internet, or a client could forge those headers itself.

Set `APP_URL` to the public HTTPS address. It is what signed links in verification and password-reset emails are built from.

## Day two

### Backups

Everything worth keeping is in PostgreSQL. Substitute the user and database from your `.env`:

```bash
docker compose exec -T postgres pg_dump -U postgres ignite > ignite-backup.sql
```

Restore into a running stack:

```bash
docker compose exec -T postgres psql -U postgres -d ignite < ignite-backup.sql
```

### Upgrading Ignite

```bash
git pull
docker compose up -d --build
```

The `migrate` service reruns on every `up` and must finish before `web` starts. Re-seeding is safe: in production the seeder only creates roles, through `updateOrCreate`.

::: danger Never delete the Postgres volume to clear a startup error
`postgres-data-production` holds all of your data, and `docker compose down -v` deletes it permanently.

The error you are most likely to meet is PostgreSQL refusing a volume it considers wrongly laid out. Version 18 keeps its files in a major-version subdirectory of `/var/lib/postgresql`, and refuses to start if it finds a data directory at the root of that path instead, which is exactly what a volume created by an older image looks like. The answer to a major-version upgrade is `pg_upgrade` against a backup, never wiping the volume.
:::

### Reading logs

```bash
docker compose logs -f web
```

With `APP_DEBUG=false`, this is the only place a real error appears. Visitors see the branded error page and nothing else.

## The image

One `Dockerfile` at the repository root defines five stages.

| Stage | Purpose |
| --- | --- |
| `runtime` | FrankenPHP on PHP 8.5 plus the extensions `artisan` needs: `pdo_pgsql`, `intl`, `zip`, `bcmath`, `opcache` |
| `base` | `runtime` plus the build tooling: Node 22, Composer, git and unzip |
| `development` | `base` plus Xdebug and the development entrypoint, used by `compose.dev.yaml` |
| `builder` | Installs the PHP and Node dependencies, runs `npm run build`, then discards `node_modules` |
| `production` | `runtime` again, copying the finished application out of `builder` |

`development` and `production` both trace back to `runtime`, which declares the extension list once. `production` derives from `runtime` directly rather than from `base`, so the shipped image carries no Node, Composer or git.

`builder` is based on a PHP image rather than a plain Node one because `npm run build` shells out to `php artisan` through the Wayfinder and i18n Vite plugins, so PHP has to be present at build time too.

It copies `composer.json` and `composer.lock`, then `package.json` and `package-lock.json`, and installs from those before the rest of the source arrives. Both dependency layers are then reused on any build where only application code changed. The autoloader is generated in a later step, after the source is in place, since `composer dump-autoload` runs `package:discover` against the application.

::: warning `production` must stay the last stage in the file
A build that names no `--target` builds whichever stage comes last, and some deploy platforms offer no way to name one in their configuration. The ordering is what makes a bare `docker build .` produce the production image. Move `development` below it and you would silently ship a debug image with Xdebug in it.
:::

### Caddyfile

FrankenPHP fuses the Caddy web server and the PHP runtime into a single binary, so there is no nginx and php-fpm split, no Redis, and no separate application server. `docker/Caddyfile` configures it:

```text{3,7}
{
	frankenphp
	auto_https off
	admin off
}

:{$PORT:8080} {
	root * /app/public
	encode zstd br gzip
	php_server
}
```

The two highlighted lines are the ones that matter. `auto_https off` keeps Caddy from trying to provision its own certificate, which it must not do when something in front of it already terminates TLS. The site address binds to `$PORT` when the platform injects one and falls back to `8080` otherwise, which is what the Compose stack uses. `php_server` covers the `try_files` rewrite to `index.php` and static asset serving in one directive.

### entrypoint.sh

```sh
#!/bin/sh
set -e

if [ -z "$APP_KEY" ]; then
  echo "Ignite cannot start: APP_KEY is not set." >&2
  ...
  exit 1
fi

php artisan config:cache
php artisan route:cache
php artisan view:cache

exec "$@"
```

The caching runs at container startup rather than during the build on purpose: environment variables arrive at run time, so a `config:cache` performed while building would bake in empty values that no variable could later override. Cached configuration bypasses `env()` entirely, which makes that particular mistake very hard to diagnose.

The script then hands off to the image's `CMD`, the FrankenPHP run command.

## Mail

Ignite sends two emails, both tied to authentication: the address-verification link and the password-reset link. There is no newsletter, no reminder mail, and no third-party mail SDK in the app. Anything Laravel can send through, Ignite can send through.

Mail is deliberately unopinionated: any SMTP provider, or your own mail server, works with the standard variables. See [Configuration](/configuration) for the full list and a worked example.

::: warning Check whether your platform allows outbound SMTP before you rely on it
Many managed hosts block it on their cheaper tiers, on every port at once, to stop their address space being used for spam. It fails as a connection timeout rather than a clear error, and only once deployed, since the same credentials work from a laptop. If you hit that, send over an HTTPS API transport instead; see [Configuration](/configuration) for the two variables involved.
:::

Leaving `MAIL_MAILER=log` together with `VERIFY_EMAIL=false` is a perfectly valid single-user setup: mail is written to the log and nobody is ever asked to verify anything.

### Two ways to lock your users out

Both emails are **queued notifications**, and the verification wall gates every authenticated route in the app. That combination has two failure modes worth understanding before you enable verification.

::: danger A queue with no worker
If `QUEUE_CONNECTION` is anything other than `sync`, the notification is written to the queue and sent only when a worker picks it up. With no worker running, it is never sent, and nothing appears to be wrong: the request succeeds and the user is told a link is on its way. Either keep `QUEUE_CONNECTION=sync`, which is the `.env.example` default and sends in-request, or run `php artisan queue:work` as a long-lived process.
:::

::: danger Verification enabled before mail works
Setting `VERIFY_EMAIL=true` while mail is misconfigured means new accounts are created unverified and can reach nothing but the verification prompt. Send yourself a real verification email and click the link before turning the wall on. If you lock yourself out anyway, an existing admin can mark accounts verified from the admin panel.
:::

Ignite never lets a mail failure break registration or password reset: if the transport throws, the account is still created, the request still succeeds, and the failure is written to the log. That is a deliberate trade, and it means **your log is the only place a delivery failure is visible**, so make sure you can actually read it.

## Deliberate simplifications

This deploy shape trades operational simplicity for headroom it does not yet need. Each of these is a choice with a documented upgrade path, not an oversight:

| Concern | Current choice | Upgrade path when needed |
| --- | --- | --- |
| Queue | `QUEUE_CONNECTION=sync` | Jobs run synchronously, in-request. No worker process to keep alive. Switch to `database` or `redis` and run a `queue:work` process once background jobs need to survive request timeouts or run concurrently. |
| Cache | `CACHE_STORE=database` | No Redis dependency; cache reads and writes hit PostgreSQL. Move to `redis` if cache traffic becomes a bottleneck. |
| Session | `SESSION_DRIVER=database` | Same trade-off as cache: one less moving part, at the cost of a little more database load per request. |
| Rendering | No SSR | The image builds the client bundle only (`npm run build`), not `build:ssr`. Inertia SSR would need its own long-running Node process; add it if first-paint or SEO requirements change. |

All of that state lives in the single PostgreSQL instance. There is no Redis, no dedicated worker, and no SSR server.

## Running on a platform instead of Compose

The same `Dockerfile` deploys unchanged to a container platform, which is how the hosted version of Ignite runs. Two details of that setup are worth borrowing.

Platform variables replace `.env` entirely. They are injected into the process environment, and Laravel's dotenv loader never overwrites a variable that is already set, so no `.env` file is needed at run time. That is the same mechanism `env_file:` uses in Compose.

Migrations belong in a pre-deploy hook rather than the entrypoint, for the reason given above. `railway.json` in the repository shows the shape:

```json{8}
{
	"$schema": "https://railway.com/railway.schema.json",
	"build": {
		"builder": "DOCKERFILE",
		"dockerfilePath": "./Dockerfile"
	},
	"deploy": {
		"preDeployCommand": "/bin/sh -c 'php artisan migrate --force && php artisan db:seed --force'",
		"healthcheckPath": "/up",
		"healthcheckTimeout": 300,
		"restartPolicyType": "ON_FAILURE",
		"restartPolicyMaxRetries": 3
	}
}
```

The highlighted line wraps both commands in `/bin/sh -c '...'`, and that wrapping is required rather than stylistic. A pre-deploy step on a Dockerfile-based build has no implicit shell, so a bare `command1 && command2` string would run only the first command and treat `&&` as a literal argument. Giving the chain a shell is what makes both run in sequence.

`/up` is the health endpoint, registered in `bootstrap/app.php`.
