# Architecture

Ignite is a Laravel 13 + Vue 3 app connected by Inertia.js: Laravel controllers render Vue page components directly, with no separate REST/JSON API layer for the frontend to consume.

```
Browser (SPA)
Vue 3 + TypeScript + Inertia Client + Tailwind CSS
        |  Inertia protocol (JSON over HTTP)
        v
Laravel Backend
Controllers -> Models -> Database (PostgreSQL)
Fortify (auth) + Inertia server-side adapter + Eloquent ORM
```

## Backend

### Routes

Routes are split by feature under `routes/`, not kept in one file:

- `routes/web.php`: public routes and the dashboard
- `routes/auth.php`: authentication (Fortify-backed)
- `routes/goals.php`: goal management, including nested resources like `goals/{goal}/entries`
- `routes/settings.php`: user settings

Resource routes follow standard RESTful conventions and are grouped with `Route::controller(...)->group(...)` per feature. See `routes/goals.php` for the pattern.

### Controllers

Grouped by feature in subdirectories under `app/Http/Controllers/`:

- `Auth/`: authentication (works with Fortify's action pipeline, but ships its own controllers and Vue pages rather than Fortify's built-in views)
- `Goals/`: `GoalController`, `GoalEntryController`
- `Settings/`: user settings

Every controller returns an Inertia response (`Inertia::render(...)`), never raw JSON, and uses route model binding for model parameters. See `app/Http/Controllers/Goals/GoalController.php` for a representative example.

### Models

The core data graph, all under `app/Models/`:

- `Goal`: the central model. `$with` eager-loads `category` and `milestones` by default (entries are excluded from the default load for performance; `GoalController::show` loads a capped set of entries plus a lightweight `chartEntries` set separately). Carries accessors such as `getProgressPercentageAttribute()` and methods like `markAsCompleted()`. `status` is one of `not_started`, `in_progress`, `completed`, `paused`, `abandoned`; `priority` is one of `low`, `medium`, `high`. See [Goal Types](/features/goal-types) for `type` and `direction`.
- `GoalEntry`: incremental progress records tied to a goal, with a timestamp.
- `Category`: user-defined grouping for goals.
- `Milestone`: checkpoints that break a goal into steps.
- `Achievement` / `UserAchievement`: gamification records.

Relationships are declared the standard Eloquent way (`belongsTo`, `hasMany`) on each model; see `app/Models/Goal.php` for the full relationship set and accessors.

### Middleware

`HandleInertiaRequests` shares common data (auth user, locale, supported locales, flash messages) with every Inertia response. `SetLocale` resolves and applies the request locale. `HandleAppearance` supports dark mode.

## Frontend

### Directory layout

```
resources/js/
├── app.ts / ssr.ts       # entry points
├── actions/              # Wayfinder-generated actions (generated, do not edit)
├── routes/                # Wayfinder-generated route helpers (generated, do not edit)
├── wayfinder/              # Wayfinder config (generated, do not edit)
├── components/            # reusable Vue components (ui/ = shadcn-vue primitives)
├── composables/           # Vue composables (useAppearance, useTwoFactorAuth, ...)
├── layouts/                # page layout shells
├── pages/                  # Inertia page components, mirroring controller structure
└── types/                  # TypeScript type definitions
```

Page components under `pages/` mirror the backend controller structure (e.g. `pages/Goals/{Index,Create,Edit,Show}.vue` for `GoalController`) and receive their data as props passed straight from `Inertia::render()`.

### Inertia data flow

Controllers call `Inertia::render('PageName', $data)`; the matching Vue component under `resources/js/pages/` receives `$data` as props. Forms submit through Inertia's `useForm()` composable, and successful responses trigger client-side navigation without a full page reload; there is no REST client, fetch layer, or separate API schema to keep in sync.

### Wayfinder route helpers

[Laravel Wayfinder](https://github.com/laravel/wayfinder) generates type-safe TypeScript route helpers from the backend route definitions, into `resources/js/routes/` and `resources/js/actions/`. This gives autocomplete and type checking for route calls in Vue components (e.g. `goals.show({ goal: id })`) instead of hand-written URL strings. These directories are generated; do not edit them directly. Configured in `vite.config.ts` with `formVariants: true`.

### TypeScript types

- `resources/js/types/models.d.ts`: interfaces mirroring the Eloquent models (`Goal`, `GoalEntry`, `Category`, `Milestone`, ...)
- `resources/js/types/index.d.ts`: Inertia page props and shared/global types (`User`, `PageProps`, `NavItem`, ...)
- `resources/js/types/globals.d.ts`: global type declarations

The path alias `@/*` maps to `resources/js/*`.

### State management

There is no global state library (Pinia/Vuex) in use. Page state is owned by Inertia props, cross-cutting logic lives in composables, and local component state uses `ref()`/`reactive()`. Pinia is a candidate only if client-side state needs to persist across navigations, gets shared across many unrelated components, or real-time updates (websockets) enter the picture.

## Further reading

- [Self-Hosting](/self-hosting) for how this app is actually deployed to production
- [Testing](/testing) for how backend and frontend code is tested
