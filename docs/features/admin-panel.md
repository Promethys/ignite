# Admin Panel

## What it is

A Filament v5 panel mounted at `/admin` for account operations and aggregate numbers. It never shows what users write:

- **Users**: list, view, edit and create accounts, identified by email. The display name is set on creation and never shown afterwards. Admins can verify an email, change roles, locale and timezone, reset a password and delete an account.
- **Stats widget**: total users, new users, goals created, entries logged, completion rate, and the abandonment rate (share of goals with `status: abandoned` out of all goals ever created). Additional widgets cover the latest registrations and registrations/entries per day.

Goals, entries, milestones and categories are not reachable from the panel. The goal, entry and milestone policies grant nothing to the `admin` role: only the owner can view, update or delete their own content.

Admins reach the panel from the app's user menu (**Admin panel**), and return with **Back to the app** in the panel's user menu. The app link only appears for users with the `admin` role: the shared Inertia prop `auth.adminPanelUrl` is `null` for everyone else. The panel runs in SPA mode, so the dashboard URL is listed in `spaUrlExceptions()` to make that link a full page load into the Inertia app.

## Prerequisites

An existing user account. Only users with the `admin` role (`spatie/laravel-permission`) can access the panel; `User::canAccessPanel()` checks `isAdmin()`, which is `hasRole('admin')`.

## The command: `app:make-admin`

Promoting a user to admin is a console command, not a UI action:

```bash
php artisan app:make-admin <user> --force
```

Exact signature (`app/Console/Commands/MakeUserAdminCommand.php`):

```text
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
- Confirm the panel has no Goals entry and that user pages show the email, not the name.

See [Configuration](/configuration) for the full environment variable reference.
