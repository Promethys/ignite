# Getting Started

This page covers the shortest path from a fresh clone to a running app. For prerequisite details, troubleshooting, and the manual setup in full, see [Installation](/installation).

## With Docker

The recommended path. It needs Docker Engine with the Compose v2 plugin, and nothing else.

```bash
git clone https://github.com/Promethys/ignite.git
cd ignite
cp .env.example .env
docker compose -f compose.dev.yaml up -d --build
```

That builds the images, installs both dependency sets, generates your application key, migrates and seeds the database, and starts the app with hot reload. Visit `http://localhost:8080` and sign in with `admin@example.com` / `password`.

::: tip Why `.env` comes first
Compose needs the file on disk to create the database service and to inject variables into the containers. Both happen before any container exists, so copying it is a genuine first step rather than something the startup script could handle. [Installation](/installation) explains what does and does not end up inside the image.
:::

## Without Docker

Prerequisites, in short: PHP 8.5+, Node.js 22+, Composer, and a PostgreSQL database.

### 1. Clone the repository

```bash
git clone https://github.com/Promethys/ignite.git
cd ignite
```

### 2. Install dependencies

```bash
composer install
npm install
```

### 3. Set up your environment

```bash
cp .env.example .env
php artisan key:generate
```

### 4. Configure your database

Ignite uses PostgreSQL by default. Set the `DB_*` variables in `.env` and create the database. See [Installation](/installation) for connection details and Windows-specific notes.

### 5. Run migrations and seed

```bash
php artisan migrate --seed
```

This creates the schema and, local/dev only (never in production), a default admin account (`admin@example.com` / `password`).

### 6. Start the app

```bash
composer dev
```

This runs the Laravel server, the queue worker, and the Vite dev server concurrently. Visit `http://localhost:8000`.

## Next steps

- [Installation](/installation) for detailed setup options and troubleshooting
- [Self-Hosting](/self-hosting) to run your own instance for real
- [Configuration](/configuration) for environment variables and app settings
- [Goal Types](/features/goal-types) to learn how goals, entries, and milestones work
