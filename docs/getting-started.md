# Getting Started

This page covers the shortest path from a fresh clone to a running app. For full prerequisite details and alternative setup paths, see [Installation](/installation).

Prerequisites, in short: PHP 8.5+, Node.js 22+, Composer, and a PostgreSQL database.

## 1. Clone the repository

```bash
git clone https://github.com/Promethys/ignite.git
cd ignite
```

## 2. Install dependencies

```bash
composer install
npm install
```

## 3. Set up your environment

```bash
cp .env.example .env
php artisan key:generate
```

## 4. Configure your database

Ignite uses PostgreSQL by default. Set the `DB_*` variables in `.env` and create the database. See [Installation](/installation) for connection details and Windows-specific notes.

## 5. Run migrations and seed

```bash
php artisan migrate --seed
```

This creates the schema and, local/dev only (never in production), a default admin account (`admin@example.com` / `password`).

## 6. Start the app

```bash
composer dev
```

This runs the Laravel server, the queue worker, and the Vite dev server concurrently. Visit `http://localhost:8000`.

## Next steps

- [Installation](/installation) for detailed setup options and troubleshooting
- [Configuration](/configuration) for environment variables and app settings
- [Goal Types](/features/goal-types) to learn how goals, entries, and milestones work
