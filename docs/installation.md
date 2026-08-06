# Installation

There are two supported ways to set up Ignite for local development.

**Docker is the recommended path.** It needs nothing on your machine except Docker itself, and it gives every contributor the same PHP, Node and PostgreSQL versions. **The manual path stays fully supported** for anyone who would rather run the toolchain natively.

Both paths described here are for local development. To run Ignite as a real deployment, see [Self-Hosting](/self-hosting).

## Option A: Docker

### Prerequisites

Docker Engine with the Compose v2 plugin. Docker Desktop bundles both.

```bash
docker --version
docker compose version
```

That is the whole list. PHP, Node, Composer and PostgreSQL all live inside the containers.

### 1. Clone the repository

```bash
git clone https://github.com/Promethys/ignite.git
cd ignite
```

### 2. Create your environment file

```bash
cp .env.example .env
```

Leave the `DB_*` values as they ship. Compose creates the PostgreSQL database from them and overrides the host and port for you.

::: warning `.env` has to exist before any Docker command
Compose reads it for two unrelated reasons: `env_file:` injects it into the app containers, and `${DB_DATABASE}`, `${DB_USERNAME}` and `${DB_PASSWORD}` are interpolated into the `postgres` service definition. Both happen before any container exists, which is why copying the file is a real first step and not something the startup script could do for you. Without it, Compose fails before it builds anything.

The file is deliberately excluded from the images by `.dockerignore`. It never ships inside a container. Compose turns it into real environment variables at run time, which is why an empty `.env` inside a running container is normal and correct.
:::

### 3. Start the stack

```bash
docker compose -f compose.dev.yaml up -d --build
```

`--build` guarantees the containers match the current `Dockerfile` instead of reusing an image that happens to be tagged already. Later restarts do not need it; see [everyday commands](#everyday-commands) below.

The first run takes a few minutes while the images build. On boot the `web` container installs the PHP dependencies if `vendor/` is missing, generates `APP_KEY` if your `.env` does not have one yet, then runs the migrations and seeders. The `vite` container installs the Node dependencies and starts the dev server.

Both of those first two steps are skipped on later boots, so restarting is fast.

::: tip The key is generated for you here, but not when self-hosting
The development container bind-mounts your working directory, so `key:generate` writes into the `.env` on your disk and the key survives every rebuild. A production container has no bind mount, which is why [Self-Hosting](/self-hosting) makes you generate the key yourself: one written inside that container would be lost on the next recreate, taking every session and stored two-factor secret with it.
:::

| Service | What it runs | Reachable at |
| --- | --- | --- |
| `web` | FrankenPHP serving the application | `http://localhost:8080` |
| `vite` | Vite dev server and hot module replacement | `http://localhost:5173` |
| `postgres` | PostgreSQL 18 | `localhost:5433` |

PostgreSQL is published on **5433**, not the usual 5432, so it cannot collide with an instance you already run natively.

### 4. Visit the application

```text
http://localhost:8080
```

Sign in with `admin@example.com` / `password`, created by the seeder. The optional demo dataset described in step 7 of the manual path works here too, run through `docker compose -f compose.dev.yaml exec web`.

Change the published port by setting `APP_PORT` in `.env`, and match `APP_URL` to it.

### Everyday commands

```bash
docker compose -f compose.dev.yaml logs -f web       # follow the app log
docker compose -f compose.dev.yaml exec web bash     # shell inside the app
docker compose -f compose.dev.yaml exec web php artisan migrate
docker compose -f compose.dev.yaml exec web php artisan test
docker compose -f compose.dev.yaml exec vite npm run lint
docker compose -f compose.dev.yaml down              # stop, keeping the database
```

Editing a `.vue`, `.ts` or `.css` file reloads the browser. Editing PHP takes effect on the next request. Both come from the same bind mount, so neither needs a rebuild. Rebuild the images only when the `Dockerfile` itself changes, with `up -d --build`.

### How hot reload works in a container

`vite.config.ts` carries four settings that exist purely for this setup:

- `host: '0.0.0.0'` so the dev server accepts connections from outside its own container.
- A fixed `hmr.host` so the browser opens its websocket back to your machine rather than to the container's internal hostname, which does not resolve from the host.
- `watch: { usePolling: true }` because filesystem change events do not cross a bind mount reliably on Windows or macOS. Without it, saving a file changes nothing on screen.
- `cors.origin`, the list of origins the dev server will serve assets to. It covers `localhost`, `127.0.0.1` and `[::1]` on any port.

`server.origin` and `cors.origin` work together. `laravel-vite-plugin` derives the CORS list from `server.origin` when `cors` is unset, so both are declared: change one and set the other to match, or the browser rejects every asset the page requests.

If a page loads but arrives unstyled and without any interactivity, Vite is not running. Check `logs -f vite`.

### Performance on Windows and macOS

Reads from a bind-mounted working directory cross a filesystem boundary: WSL2 on Windows, virtiofs on macOS. A single file read costs around 5 ms there, against around 0.01 ms from a Docker volume, and booting Laravel touches a few thousand files.

The dev stack is laid out around that. `/app/vendor` and `/app/node_modules` are named volumes, so the dependency trees stay on the Linux side, and your own code stays on the bind mount so edits apply immediately.

Hot reload is polled rather than event-driven, since change events do not cross the boundary either. On Windows, a save reaches the browser in about five seconds.

Two ways to avoid the boundary entirely:

- Keep the clone inside the Linux filesystem, under `\\wsl$\` on Windows with the WSL2 integration enabled. This is a per-developer choice and changes nothing in the repository.
- Use [Option B](#option-b-manual-installation). A native toolchain has no boundary to cross.

Linux hosts are unaffected.

### Debugging with Xdebug

The development image ships Xdebug in `start_with_request=trigger` mode, so it stays dormant until a request carries the trigger, and normal browsing keeps full speed. Point your IDE at `host.docker.internal` with the IDE key `DOCKER`.

The default mode is `develop,debug`, which covers step debugging and readable stack traces. `coverage` and `profile` are not enabled.

Xdebug reads the `XDEBUG_MODE` environment variable at run time, so any mode can be enabled for a single command without rebuilding:

```bash
docker compose -f compose.dev.yaml exec -e XDEBUG_MODE=coverage web php artisan test --coverage
```

The build arguments at the top of the `development` stage in `Dockerfile` control the default mode, host, IDE key and log level. Setting `XDEBUG_ENABLED=false` builds the image without it.

::: tip `vendor/` and `node_modules/` live in Docker volumes, not your working directory
The dev stack bind-mounts your working directory, then mounts named volumes over `/app/vendor` and `/app/node_modules`. The containers install their own dependencies against the PHP and Node versions in the image, and a native install on your host is never used.

- A named volume shadows the path inside the container without altering it on disk. Your host `vendor/` and `node_modules/` stay intact and keep serving native tooling such as Pint, PHPStan, ESLint and IDE autocomplete.
- The two copies are independent. After changing a dependency, install on the host for your tooling, and run `exec web composer install` or `exec vite npm install` for the containers.
- `down -v` removes these volumes along with the database. The next boot reinstalls both.
:::

## Option B: Manual installation

### Prerequisites

- **PHP** `^8.5` (the exact constraint from `composer.json`)
- **Node.js** 22
- **Composer**
- **PostgreSQL** (the app's default database, configured in step 5 below). No minimum version is enforced by the app; CI runs against PostgreSQL 18.

SQLite is used only for the automated test suite (via `phpunit.xml`), not for local development or production.

Check your installed versions:

```bash
php --version         # Should satisfy ^8.5
node --version        # Should be 22+
composer --version
npm --version
psql --version        # PostgreSQL client
```

### 1. Clone the repository

```bash
git clone https://github.com/Promethys/ignite.git
cd ignite
```

### 2. Install PHP dependencies

```bash
composer install
```

### 3. Install Node dependencies

```bash
npm install
```

### 4. Set up the environment file

```bash
cp .env.example .env
php artisan key:generate
```

`.env.example` defaults `APP_URL` to the port the Docker setup uses. For a native `php artisan serve`, change it:

```ini
APP_URL=http://localhost:8000
```

It only affects URLs built outside a request, such as the links in verification and password-reset mail, so a mismatch is easy to miss.

See [Configuration](/configuration) for a full reference of the environment variables in `.env.example`.

### 5. Configure the database

Ignite uses PostgreSQL by default. Edit `.env`:

```ini
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=ignite
DB_USERNAME=postgres
DB_PASSWORD=your_password
```

Create the database:

::: code-group

```bash [createdb]
createdb ignite
```

```bash [psql]
psql -U postgres -c "CREATE DATABASE ignite;"
```

:::

::: warning Windows
The `pdo_pgsql` extension is commented out in a default PHP install. Uncomment it in `php.ini` and restart PHP, or every database command will fail with a driver-not-found error.
:::

### 6. Run migrations

```bash
php artisan migrate --seed
```

`--seed` runs the default `DatabaseSeeder`, which creates the roles and, outside production, a single account: `admin@example.com` / `password` (plus a bare `test@example.com` user).

### 7. Seed demo data (optional)

For a richer dataset with sample goals, categories, and achievements, run the dedicated demo seeder separately:

```bash
php artisan db:seed --class=InitDataSeeder
```

This creates three additional test users:

- `demo@ignite.test` / `password` (12 goals, varied states)
- `active@ignite.test` / `password` (5 goals in progress)
- `new@ignite.test` / `password` (clean slate)

To remove this demo data without touching the schema, run `php artisan db:seed --class=RevertDataSeeder`. To start over completely, `php artisan migrate:fresh --seed` drops all tables and re-runs migrations plus the default seeder.

### 8. Build assets

::: code-group

```bash [Development]
npm run dev
```

```bash [Production]
npm run build
```

:::

### 9. Start the development server

`composer dev` runs the Laravel server, the queue worker, and the Vite dev server concurrently, and is the preferred entry point. The individual commands stay available when you want to restart or watch a single process on its own.

::: code-group

```bash [All at once]
composer dev
```

```bash [Individually]
php artisan serve                   # Backend server (localhost:8000)
npm run dev                         # Vite dev server (HMR)
php artisan queue:listen --tries=1  # Queue worker
```

:::

### 10. Visit the application

```text
http://localhost:8000
```

## Development workflow

Day to day the loop is a single command, whichever path you chose:

::: code-group

```bash [Docker]
docker compose -f compose.dev.yaml up -d
```

```bash [Manual]
composer dev
```

:::

An SSR mode is also available for production-like server-side rendering testing. It is only wired up for the manual path:

```bash
composer dev:ssr
```
