# Admin Panel

## What it is

A Filament v5 panel mounted at `/admin` for operational visibility into the app. It's read-mostly:

- **Users**: full resource (list, view, edit, create) with a related-goals view.
- **Goals**: read-only, list and view pages only, no create or edit.
- **Stats widget**: total users, new users, goals created, entries logged, completion rate, and the abandonment rate (share of goals with `status: abandoned` out of all goals ever created). Additional widgets cover recent activity and registrations/entries per day.

There is no in-app link to `/admin`. You navigate to it directly by URL.

## Prerequisites

An existing user account. Only users with the `admin` role (`spatie/laravel-permission`) can access the panel; `User::canAccessPanel()` checks `isAdmin()`, which is `hasRole('admin')`.

## The command: `app:make-admin`

Promoting a user to admin is a console command, not a UI action:

```bash
php artisan app:make-admin <user> --force
```

Exact signature (`app/Console/Commands/MakeUserAdminCommand.php`):

```
app:make-admin
    {user : The ID or email of the user}
    {--force}
```

`<user>` accepts either a numeric ID or an email address. Without `--force`, the command prompts for confirmation; `--force` skips the prompt, which is what you need on a non-interactive console (for example, a Railway console session).

## Seeding behavior

`RolesTableSeeder` creates the `admin` role on every deploy (`Role::updateOrCreate(['name' => 'admin'])`), but it never assigns that role to anyone in production. This is deliberate: no admin credentials ship in a public repository. The only way to get the first admin in production is to run `app:make-admin` by hand against a real console.

Locally (any non-`production` environment), `DatabaseSeeder` also runs `UsersTableSeeder`, which seeds an `admin@example.com` / `password` account and assigns it the `admin` role directly, no command needed. This only happens outside `production`.

## How to verify

- Run `php artisan app:make-admin you@example.com --force` and confirm it reports the role was assigned (or already present).
- Log in as that user and visit `/admin`; the panel should load. A non-admin user hitting `/admin` should be denied.
- Confirm the Goals resource in the panel has no create or edit action, only list and view.

See [Configuration](/configuration) for the full environment variable reference.
