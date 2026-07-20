# Installation

This page covers the full local development setup: prerequisites, initial setup, and the day-to-day development workflow.

## Prerequisites

- **PHP** `^8.5` (the exact constraint from `composer.json`)
- **Node.js** 22
- **Composer**
- **PostgreSQL** (the app's default database; see [Database](#configure-the-database) below)

SQLite is used only for the automated test suite (via `phpunit.xml`), not for local development or production.

Check your installed versions:

```bash
php --version        # Should satisfy ^8.5
node --version        # Should be 22+
composer --version
npm --version
psql --version         # PostgreSQL client
```

## Initial setup

### 1. Clone the repository

```bash
git clone https://github.com/Promethys/ignite
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

See [Configuration](/configuration) for a full reference of the environment variables in `.env.example`.

### 5. Configure the database

Ignite uses PostgreSQL by default. Edit `.env`:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=ignite
DB_USERNAME=postgres
DB_PASSWORD=your_password
```

Create the database:

```bash
createdb ignite
# or via psql:
# psql -U postgres -c "CREATE DATABASE ignite;"
```

On Windows, make sure the `pdo_pgsql` extension is enabled in `php.ini`.

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

### 8. Build assets

```bash
# Development build
npm run dev

# Or a production build
npm run build
```

### 9. Start the development server

```bash
composer dev
```

This is the preferred command: it runs the Laravel server, the queue worker, and the Vite dev server concurrently.

Or start each process individually:

```bash
php artisan serve                   # Backend server (localhost:8000)
npm run dev                         # Vite dev server (HMR)
php artisan queue:listen --tries=1  # Queue worker
```

### 10. Visit the application

```
http://localhost:8000
```

## Development workflow

Day to day, `composer dev` is the preferred entry point since it starts the server, queue worker, and Vite together in one terminal. The individual commands above remain available when you want to run, restart, or watch a single process on its own.

An SSR mode is also available for production-like server-side rendering testing:

```bash
composer dev:ssr
```
