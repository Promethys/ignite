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

The first run takes a few minutes while the images build. On boot the `web` container installs the PHP dependencies if `vendor/` is missing, generates `APP_KEY` if your `.env` does not have one yet, clears the caches, then runs the migrations and seeders. The `vite` container installs the Node dependencies and starts the dev server.

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

`vite.config.ts` carries three settings that exist purely for this setup:

- `host: '0.0.0.0'` so the dev server accepts connections from outside its own container.
- A fixed `hmr.host` so the browser opens its websocket back to your machine rather than to the container's internal hostname, which does not resolve from the host.
- `watch: { usePolling: true }` because filesystem change events do not cross a bind mount reliably on Windows or macOS. Without it, saving a file changes nothing on screen.

If a page loads but arrives unstyled and without any interactivity, Vite is not running. Check `logs -f vite`.

### Debugging with Xdebug

The development image ships Xdebug in `start_with_request=trigger` mode, so it stays dormant until a request carries the trigger, and normal browsing keeps full speed. Point your IDE at `host.docker.internal` with the IDE key `DOCKER`.

The build arguments at the top of the `development` stage in `Dockerfile` control the mode, host, IDE key and log level. Setting `XDEBUG_ENABLED=false` builds the image without it.

::: warning A native `vendor/` or `node_modules/` will be reused as-is
The dev containers bind-mount your whole working directory, so directories built by a native install are picked up unchanged. If you have previously run `composer install` or `npm install` on the host with different PHP or Node versions, delete both directories and let the containers rebuild them, or you will chase errors that have nothing to do with your code.
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
