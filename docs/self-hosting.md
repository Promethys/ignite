# Self-Hosting

Ignite is source-available (FSL-1.1-MIT) and self-hostable. This page documents the deploy shape actually shipped in this repo (the one running in production on Railway), not a generic Laravel deployment tutorial. For local development, see [Installation](/installation).

## The shape: one container

Production runs as a single [FrankenPHP](https://frankenphp.dev/) container. FrankenPHP fuses the Caddy web server and the PHP runtime into one binary, so there is no separate nginx/php-fpm split, no Redis, and no queue worker process: the app, the web server, and the app server are all inside one image.

The relevant artifacts live under `docker/production/frankenphp/`:

- `Dockerfile`: multi-stage build
- `Caddyfile`: Caddy site config, handed to FrankenPHP at runtime
- `entrypoint.sh`: startup script run before the app boots

### Dockerfile

Two stages:

1. **`builder`**: based on the `dunglas/frankenphp` image (chosen as the build base specifically because `npm run build` shells out to `php artisan` via the Wayfinder and i18n Vite plugins, so PHP has to be present at build time too). It installs Node 22 and the PHP extensions needed to run `artisan` (`pdo_pgsql`, `intl`, `zip`, `bcmath`, `opcache`), runs `composer install --no-dev`, then `npm ci && npm run build`, and drops `node_modules` afterward.
2. **`production`**: a fresh copy of the same FrankenPHP base with the same PHP extensions, `COPY --from=builder` pulls over the fully built app (vendor and `public/build` included), and the Railway-tuned `Caddyfile` and `entrypoint.sh` are copied in. `APP_ENV=production` and `APP_DEBUG=false` are baked in as image env. The entrypoint is the container `ENTRYPOINT`; the default `CMD` is `frankenphp run --config /etc/caddy/Caddyfile`.

### Caddyfile

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

The two highlighted lines are the ones that matter:
- `auto_https off` because Railway terminates TLS at its edge and forwards plain HTTP to the container; without a hostname in the site address, Caddy would otherwise try to provision its own certificate.
- The bind address is `:{$PORT:8080}`, i.e. whatever port Railway injects via `$PORT`, falling back to `8080` for local experiments. `php_server` handles the `try_files → index.php` rewrite and static asset serving in one directive.

### entrypoint.sh

```sh{4-6}
#!/bin/sh
set -e

php artisan config:cache
php artisan route:cache
php artisan view:cache

exec "$@"
```

Caching is deliberately done here, at container startup, and not during the Docker build: Railway injects environment variables at runtime, not at build time, so `config:cache` has to run after env is available or it would bake in empty values. The script then `exec`s into whatever `CMD` the image was given (the FrankenPHP run command).

## railway.json

```json{8}
{
	"$schema": "https://railway.com/railway.schema.json",
	"build": {
		"builder": "DOCKERFILE",
		"dockerfilePath": "docker/production/frankenphp/Dockerfile"
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

### The `/bin/sh -c` gotcha

The `preDeployCommand` runs `php artisan migrate --force`, then `php artisan db:seed --force`. Both are wrapped in a single string passed to `/bin/sh -c '...'`.

This wrapping is required, not stylistic. Railway's pre-deploy step, under a Dockerfile-based build, has no implicit shell: if `preDeployCommand` were given as a bare `command1 && command2` string (or an array of two commands), only the first command would actually run and the `&&` would be interpreted literally rather than as a shell operator. Wrapping the whole chain in `/bin/sh -c '...'` gives `&&` a shell to be interpreted by, so both `migrate` and `db:seed` run in sequence, in one process.

Seeding on every deploy is safe here because production only runs the roles seeder through `updateOrCreate` (the user-account seeder is env-gated off in production), so re-running it is idempotent.

## Mail

Ignite sends two emails, both tied to authentication: the address-verification link and the password-reset link. There is no newsletter, no reminder mail, and no third-party mail SDK in the app. Anything Laravel can send through, Ignite can send through.

Mail is deliberately unopinionated: any SMTP provider, or your own mail server, works with the standard variables. See [Configuration](/configuration) for the full list and a worked example.

::: warning Check whether your platform allows outbound SMTP before you rely on it
Many managed hosts block it on their cheaper tiers, on every port at once, to stop their address space being used for spam. It fails as a connection timeout rather than a clear error, and only once deployed, since the same credentials work from a laptop. If you hit that, send over an HTTPS API transport instead; see [Configuration](/configuration) for the two variables involved.
:::

Leaving `MAIL_MAILER=log` together with `VERIFY_EMAIL=false` is a perfectly valid single-user setup: mail is written to `storage/logs/laravel.log` and nobody is ever asked to verify anything.

### Two ways to lock your users out

Both emails are **queued notifications**, and the verification wall gates every authenticated route in the app. That combination has two failure modes worth understanding before you enable verification.

::: danger A queue with no worker
If `QUEUE_CONNECTION` is anything other than `sync`, the notification is written to the queue and sent only when a worker picks it up. With no worker running, it is never sent, and nothing appears to be wrong: the request succeeds and the user is told a link is on its way. Either keep `QUEUE_CONNECTION=sync`, which is the `.env.example` default and sends in-request, or run `php artisan queue:work` as a long-lived process.
:::

::: danger Verification enabled before mail works
Setting `VERIFY_EMAIL=true` while mail is misconfigured means new accounts are created unverified and can reach nothing but the verification prompt. Send yourself a real verification email and click the link before turning the wall on. If you lock yourself out anyway, an existing admin can mark accounts verified from the admin panel.
:::

Ignite never lets a mail failure break registration or password reset: if the transport throws, the account is still created, the request still succeeds, and the failure is written to the log. That is a deliberate trade, and it means **your log is the only place a delivery failure is visible**, so make sure you can actually read it (see the logging note in [Configuration](/configuration); on a container platform that means `LOG_CHANNEL=stderr`).

## Deliberate simplifications

This deploy shape trades operational simplicity for headroom it doesn't yet need. Each of these is a choice with a documented upgrade path, not an oversight:

| Concern | Current choice | Upgrade path when needed |
| --- | --- | --- |
| Queue | `QUEUE_CONNECTION=sync` (set via a Railway environment variable, overriding the `.env.example` default of `database`) | Jobs run synchronously, in-request. No worker process to keep alive. Switch to `database` or `redis` and run a `queue:work` process (e.g. a second Railway service) once background jobs need to survive request timeouts or run concurrently. |
| Cache | `CACHE_STORE=database` | No Redis dependency; cache reads/writes hit Postgres. Move to `redis` if cache traffic becomes a bottleneck. |
| Session | `SESSION_DRIVER=database` | Same trade-off as cache: one less moving part, at the cost of a bit more Postgres load per request. |
| Rendering | No SSR | The image only builds the client bundle (`npm run build`), not `build:ssr`. Inertia SSR would need its own long-running Node process; add it if first-paint/SEO requirements change. |

All of the above state (sessions, cache, queue-when-not-sync) lives in the single managed Postgres instance; there is currently no Redis, no dedicated worker dyno, and no SSR server in production.

## Not covered

::: warning Legacy deployment stack
The repo also contains a legacy VPS-style deployment stack: `compose.prod.yaml` and `docker/production/{nginx,php-fpm}/`. This predates the FrankenPHP/Railway setup and is dead code, not documented here. Do not use it as a reference for how production actually runs.
:::
