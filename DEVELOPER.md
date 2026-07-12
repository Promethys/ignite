# Ignite - Developer Documentation 🔥

A Laravel 13 + Vue 3 + Inertia.js goal tracking application with authentication, gamification, internationalization, and progress visualization features.

## 📋 Table of Contents

- [Tech Stack](#tech-stack)
- [Project Architecture](#project-architecture)
- [Getting Started](#getting-started)
- [Development Workflow](#development-workflow)
- [Database Structure](#database-structure)
- [Frontend Architecture](#frontend-architecture)
- [Backend Architecture](#backend-architecture)
- [Testing](#testing)
- [Code Style & Conventions](#code-style--conventions)
- [Common Tasks](#common-tasks)
- [Troubleshooting](#troubleshooting)

---

## 🛠️ Tech Stack

### Backend
- **PHP**: 8.5
- **Laravel**: 13.x
- **Database**: PostgreSQL (SQLite `:memory:` for the test suite)
- **Authentication**: Laravel Fortify (session-based, with 2FA support)
- **Internationalization**: `laravel-vue-i18n` bridging Laravel `lang/` files to Vue (English + French)

### Frontend
- **Node.js**: 22.14.0
- **Vue.js**: 3.x (Composition API with `<script setup>`)
- **TypeScript**: Full TypeScript support
- **Inertia.js**: Server-side routing with SPA experience
- **UI Components**: Reka UI primitives (shadcn-vue-style components live in `resources/js/components/ui/`)
- **Styling**: Tailwind CSS v4 (via the `@tailwindcss/vite` plugin; no `tailwind.config.js`, theme tokens live in `resources/css/app.css`)
- **Icons**: Lucide Vue

### Development Tools
- **Build Tool**: Vite
- **Testing**: PHPUnit (backend) + Vitest (frontend, `tests/js/`)
- **Code Quality**: 
  - PHP: Laravel Pint (formatting) + Larastan/PHPStan (static analysis, level 3)
  - JavaScript/TypeScript: ESLint + Prettier
- **Routing**: Wayfinder (type-safe Laravel routes for TypeScript)
- **CI/CD**: GitHub Actions (single `ci.yml`: lint + PHPUnit + Vitest, PostgreSQL service container)

---

## 🏗️ Project Architecture

### High-Level Overview

```
┌─────────────────────────────────────────────────────────┐
│                      Browser (SPA)                      │
│  Vue 3 + TypeScript + Inertia Client + Tailwind CSS     │
└────────────────────┬────────────────────────────────────┘
                     │ Inertia Protocol (JSON)
                     │
┌────────────────────▼────────────────────────────────────┐
│                   Laravel Backend                       │
│  Controllers → Models → Database (PostgreSQL)           │
│  Fortify (Auth) + Inertia Server + Eloquent ORM         │
└─────────────────────────────────────────────────────────┘
```

### Key Architectural Decisions

1. **Inertia.js as the Bridge**: 
   - Renders Vue components server-side via Laravel controllers
   - No REST API needed - controllers return Inertia responses
   - Automatic page component resolution

2. **Wayfinder for Type-Safe Routing**:
   - Generates TypeScript route helpers from Laravel routes
   - Provides type-safe route parameters
   - Enables autocomplete for routes in Vue components

3. **Component-Based UI**:
   - shadcn-vue for consistent, accessible components
   - Custom components in `resources/js/components/`
   - Layout components for different page types

4. **Feature-Based Organization**:
   - Controllers grouped by feature (Auth, Goals, Settings)
   - Corresponding Vue pages mirror controller structure
   - Type definitions in `resources/js/types/models.d.ts`

---

## 🚀 Getting Started

### Prerequisites

```bash
# Check versions
php --version        # Should be 8.5+
node --version       # Should be 22+
composer --version
npm --version
psql --version       # PostgreSQL client
```

### Initial Setup

1. **Clone the repository**
   ```bash
   git clone https://github.com/Promethys/ignite
   cd ignite
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Install Node dependencies**
   ```bash
   npm install
   ```

4. **Environment setup**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

5. **Configure database**
   
   Edit `.env` (PostgreSQL):
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

   > On Windows, ensure `pdo_pgsql` is enabled in `php.ini`.

   **Optional local auth toggle:** set `VERIFY_EMAIL=false` in `.env` to skip the email-verification wall in development. It defaults to `true`, so an unset value keeps verification enforced in production.

6. **Run migrations**
   ```bash
   php artisan migrate
   ```

7. **Seed demo data (optional)**
   ```bash
   php artisan db:seed --class=InitDataSeeder
   ```
   
   This creates 3 test users:
   - `demo@ignite.test` / `password` (12 goals, varied states)
   - `active@ignite.test` / `password` (5 goals in progress)
   - `new@ignite.test` / `password` (clean slate)

8. **Build assets**
   ```bash
   # Development build
   npm run dev
   
   # Or production build
   npm run build
   ```

9. **Start the development server**
   ```bash
   # One command runs server + queue worker + Vite concurrently:
   composer dev

   # Or start them individually:
   php artisan serve   # Terminal 1: Laravel server
   npm run dev         # Terminal 2: Vite dev server (HMR)
   ```

10. **Visit the application**
    ```
    http://localhost:8000
    ```

---

## 💻 Development Workflow

### Daily Development

```bash
# Preferred: one command runs server + queue worker + Vite concurrently
composer dev

# Or the individual processes:
php artisan serve   # Backend
npm run dev         # Frontend (HMR)
```

> A Docker Compose dev environment (`compose.dev.yaml`, 5 services) also exists but is currently experimental and not the supported path. The native flow above is what we run day to day.

### Working with Routes

#### Laravel Routes (Backend)

Define routes in route files:
```php
// routes/goals.php
Route::middleware('auth')->prefix('/goals')->group(function () {
    Route::controller(GoalController::class)->group(function () {
        Route::get('/', 'index')->name('goals.index');
        Route::get('/create', 'create')->name('goals.create');
        Route::post('/', 'store')->name('goals.store');
        Route::get('/{goal}', 'show')->name('goals.show');
        // ...
    });
});
```

#### TypeScript Routes (Frontend)

Wayfinder auto-generates TypeScript helpers:
```typescript
// resources/js/components/AppSidebar.vue
import { goals } from '@/routes';

// Usage
goals.index()              // → /goals
goals.show(5)              // → /goals/5
goals.edit(5)              // → /goals/5/edit
```

### Working with Inertia

#### Controller (Backend)

```php
// app/Http/Controllers/Goals/GoalController.php
public function index()
{
    return Inertia::render('Goals/Index', [
        'goals' => auth()->user()->goals,
        'categories' => auth()->user()->categories,
    ]);
}
```

#### Vue Component (Frontend)

```vue
<!-- resources/js/pages/Goals/Index.vue -->
<script setup lang="ts">
import { Goal, Category } from '@/types/models';

defineProps<{
    goals: Goal[];
    categories: Category[];
}>();
</script>

<template>
    <div v-for="goal in goals" :key="goal.id">
        {{ goal.title }}
    </div>
</template>
```

### Working with Forms

Inertia provides a form helper for easy form handling:

```vue
<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { goals } from '@/routes';

const form = useForm({
    title: '',
    description: '',
    type: 'simple',
});

function submit() {
    form.post(goals.store(), {
        onSuccess: () => {
            // Handle success
        },
    });
}
</script>

<template>
    <form @submit.prevent="submit">
        <input v-model="form.title" type="text" />
        <p v-if="form.errors.title">{{ form.errors.title }}</p>
        
        <button :disabled="form.processing">
            Submit
        </button>
    </form>
</template>
```

Or use Wayfinder's form variants for type-safe forms:

```vue
<script setup lang="ts">
import { GoalController } from '@/routes/goals';

// Wayfinder generates form props automatically
const formProps = GoalController.store.form();
</script>

<template>
    <Form v-bind="formProps">
        <!-- Form fields -->
    </Form>
</template>
```

### Creating New Features

**Example: Adding a new "Projects" feature**

1. **Create migration**
   ```bash
   php artisan make:migration create_projects_table
   ```

2. **Create model with factory**
   ```bash
   php artisan make:model Project -f
   ```

3. **Create controller**
   ```bash
   php artisan make:controller Projects/ProjectController
   ```

4. **Define routes**
   ```php
   // routes/projects.php
   Route::resource('projects', ProjectController::class);
   ```

5. **Create Vue pages**
   ```bash
   mkdir resources/js/pages/Projects
   touch resources/js/pages/Projects/{Index,Create,Edit,Show}.vue
   ```

6. **Create TypeScript types**
   ```typescript
   // resources/js/types/models.d.ts
   export interface Project {
       id: number;
       name: string;
       // ...
   }
   ```

7. **Run Wayfinder to generate route helpers**
   ```bash
   # Wayfinder runs automatically with npm run dev
   # Or manually:
   php artisan wayfinder:generate
   ```

---

## 🗄️ Database Structure

### Core Tables

#### **users**
- Authentication and user profile data
- Includes 2FA columns (`two_factor_secret`, `two_factor_recovery_codes`)

#### **categories**
- User-defined goal categories
- Customizable colors and icons
- Belongs to user

#### **goals**
- Main goal tracking entity
- **Types**: `simple`, `quantifiable`, `recurring`, `multi_step`
- **Statuses**: `not_started`, `in_progress`, `completed`, `paused`, `abandoned`
- **Priorities**: `low`, `medium`, `high`
- Belongs to user and category

#### **goal_entries**
- Progress records for goals
- Tracks value changes over time
- Optional notes and attachments
- Belongs to goal

#### **milestones**
- Checkpoints for multi-step goals
- Track completion status
- Optional points rewards
- Belongs to goal

#### **achievements**
- System-defined unlockable achievements
- **Types**: `goal_completion`, `streak`, `points`, `consistency`, `special`
- **Rarities**: `common`, `rare`, `epic`, `legendary`
- JSON criteria for unlocking

#### **user_achievements**
- Tracks user progress on achievements
- Stores progress percentage and unlock date
- Pivot table between users and achievements

### Database Diagram

```
users
  ├─ has many → categories
  ├─ has many → goals
  └─ belongs to many → achievements (via user_achievements)

categories
  ├─ belongs to → user
  └─ has many → goals

goals
  ├─ belongs to → user
  ├─ belongs to → category
  ├─ has many → goal_entries
  └─ has many → milestones

goal_entries
  └─ belongs to → goal

milestones
  └─ belongs to → goal

achievements
  └─ belongs to many → users (via user_achievements)

user_achievements
  ├─ belongs to → user
  └─ belongs to → achievement
```

### Seeders

#### **InitDataSeeder**
Creates comprehensive demo data:
- 3 test users with different states
- 5 categories per user
- 12+ goals with varied types and statuses
- Goal entries with realistic progress
- Milestones for multi-step goals
- 8 system achievements

Usage:
```bash
php artisan db:seed --class=InitDataSeeder
```

#### **RevertDataSeeder**
Removes all seeded data while preserving structure:
```bash
php artisan db:seed --class=RevertDataSeeder
```

#### Quick Reset
```bash
# Nuclear option: fresh migration + seed
php artisan migrate:fresh --seed

# Or just reseed
php artisan db:seed --class=RevertDataSeeder
php artisan db:seed --class=InitDataSeeder
```

---

## 🎨 Frontend Architecture

### Directory Structure

```
resources/js/
├── app.ts                    # Main app entry point
├── ssr.ts                    # Server-side rendering entry
├── actions/                  # Wayfinder generated actions (DO NOT EDIT)
├── components/               # Reusable Vue components
│   ├── ui/                   # shadcn-vue components
│   ├── AppSidebar.vue        # Main navigation
│   ├── GoalCard.vue          # Goal display component
│   └── ...
├── composables/              # Vue composables (reusable logic)
│   ├── useAppearance.ts      # Dark mode logic
│   ├── useTwoFactorAuth.ts   # 2FA logic
│   └── useInitials.ts        # Avatar initials
├── layouts/                  # Page layouts
│   ├── AppLayout.vue         # Main authenticated layout
│   ├── AuthLayout.vue        # Authentication pages layout
│   └── settings/Layout.vue   # Settings pages layout
├── lib/                      # Utilities
│   └── utils.ts              # Helper functions (cn, etc.)
├── pages/                    # Inertia page components
│   ├── Dashboard.vue
│   ├── Goals/
│   │   ├── Index.vue
│   │   ├── Create.vue
│   │   ├── Edit.vue
│   │   └── Show.vue
│   ├── auth/
│   └── settings/
├── routes/                   # Wayfinder route helpers (DO NOT EDIT)
├── types/                    # TypeScript type definitions
│   ├── index.d.ts            # Global types
│   ├── models.d.ts           # Database model types
│   └── globals.d.ts          # Inertia & global augmentations
└── wayfinder/                # Wayfinder config (DO NOT EDIT)
```

### Component Organization

#### **UI Components** (`components/ui/`)
- shadcn-vue primitives (Button, Card, Dialog, etc.)
- Accessible, unstyled components
- Customizable via Tailwind CSS

#### **Feature Components** (`components/`)
- App-specific components (GoalCard, AppSidebar, etc.)
- Business logic components
- Reusable across pages

#### **Layout Components** (`layouts/`)
- Page structure templates
- Consistent navigation and styling
- Slot-based for flexibility

#### **Page Components** (`pages/`)
- Inertia page components
- Mirror backend controller structure
- Receive props from controllers

### TypeScript Types

#### **Model Types** (`types/models.d.ts`)

```typescript
export interface Goal {
    id: number;
    user_id: number;
    category_id: number | null;
    title: string;
    description: string | null;
    icon: string | null;
    type: 'simple' | 'quantifiable' | 'recurring' | 'multi_step';
    target_value: number | null;
    current_value: number;
    unit: string | null;
    status: 'not_started' | 'in_progress' | 'completed' | 'paused' | 'abandoned';
    priority: 'low' | 'medium' | 'high';
    // ... more fields
    
    // Relationships (if loaded)
    category?: Category;
    entries?: GoalEntry[];
    milestones?: Milestone[];
}
```

#### **Global Types** (`types/index.d.ts`)

```typescript
export interface User {
    id: number;
    name: string;
    email: string;
    // ...
}

export interface PageProps {
    auth: {
        user: User;
    };
    // ... other shared props
}

export interface NavItem {
    title: string;
    href: string;
    icon?: Component;
}

export interface BreadcrumbItem {
    title: string;
    href?: string;
}
```

### State Management

**Current**: No global state management (Pinia/Vuex)

**Approach**:
- Inertia manages page state via props
- Composables for shared logic
- Local component state with `ref()` / `reactive()`

**When to add Pinia**:
- If you need client-side state persistence
- Complex state shared across many components
- Real-time updates (websockets)

### Styling

#### **Tailwind CSS**
- Utility-first CSS framework
- Configured in `tailwind.config.js`
- Custom theme colors and design tokens

#### **shadcn-vue Theming**
- CSS variables for colors
- Defined in `resources/css/app.css`
- Supports light/dark mode

#### **Dark Mode**
- Managed via `useAppearance` composable
- Persisted in localStorage
- System preference detection

---

## ⚙️ Backend Architecture

### Directory Structure

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Auth/              # Authentication controllers
│   │   ├── Goals/             # Goal management
│   │   │   ├── GoalController.php
│   │   │   └── GoalEntryController.php
│   │   └── Settings/          # User settings
│   ├── Middleware/
│   │   ├── HandleInertiaRequests.php  # Share data with frontend (auth, locale, flash)
│   │   ├── SetLocale.php              # Resolve + apply the request locale
│   │   └── HandleAppearance.php       # Dark mode middleware
│   └── Requests/              # Form request validation
│       ├── Auth/
│       └── Settings/
├── Models/                    # Eloquent models
│   ├── User.php
│   ├── Goal.php
│   ├── GoalEntry.php
│   ├── Category.php
│   ├── Milestone.php
│   ├── Achievement.php
│   └── UserAchievement.php
└── Providers/
    ├── AppServiceProvider.php
    └── FortifyServiceProvider.php
```

### Controllers

#### **Structure**
- Grouped by feature in subdirectories
- Return Inertia responses, not JSON
- Use route model binding for model parameters

#### **Example Controller**

```php
namespace App\Http\Controllers\Goals;

use App\Http\Controllers\Controller;
use App\Models\Goal;
use Inertia\Inertia;

class GoalController extends Controller
{
    public function index()
    {
        return Inertia::render('Goals/Index', [
            'goals' => auth()->user()->goals()
                ->with(['category', 'milestones'])
                ->latest()
                ->get(),
        ]);
    }
    
    public function show(Goal $goal)
    {
        // Route model binding automatically loads the goal
        // Authorize access
        $this->authorize('view', $goal);
        
        return Inertia::render('Goals/Show', [
            'goal' => $goal->load(['entries', 'milestones']),
        ]);
    }
}
```

### Models

#### **Relationships Example**

```php
// app/Models/Goal.php
class Goal extends Model
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
    
    public function entries(): HasMany
    {
        return $this->hasMany(GoalEntry::class);
    }
    
    public function milestones(): HasMany
    {
        return $this->hasMany(Milestone::class);
    }
    
    // Accessors
    public function getProgressPercentageAttribute(): float
    {
        if ($this->target_value <= 0) return 0;
        return min(($this->current_value / $this->target_value) * 100, 100);
    }
    
    // Methods
    public function markAsCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }
}
```

### Middleware

#### **HandleInertiaRequests**
Shares data with all Inertia responses:

```php
public function share(Request $request): array
{
    return [
        ...parent::share($request),
        'auth' => [
            'user' => $request->user(),
        ],
        'locale' => app()->getLocale(),
        'supportedLocales' => config('locales.supported'),
        'flash' => [
            'toast' => fn () => $request->session()->get('toast'),
        ],
    ];
}
```

Access in Vue:
```vue
<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const user = page.props.auth.user;
</script>
```

### Routes

#### **Route Organization**

```
routes/
├── web.php          # Public routes + dashboard
├── auth.php         # Authentication routes (Fortify)
├── goals.php        # Goal management routes
└── settings.php     # User settings routes
```

#### **Route Groups**

```php
// routes/goals.php
Route::middleware('auth')->prefix('/goals')->group(function () {
    Route::controller(GoalController::class)->group(function () {
        Route::get('/', 'index')->name('goals.index');
        Route::get('/create', 'create')->name('goals.create');
        Route::post('/', 'store')->name('goals.store');
        Route::get('/{goal}', 'show')->name('goals.show');
        Route::get('/{goal}/edit', 'edit')->name('goals.edit');
        Route::put('/{goal}', 'update')->name('goals.update');
        Route::delete('/{goal}', 'destroy')->name('goals.destroy');
    });
});
```

### Authentication (Fortify)

Auth is session-based via Fortify, but the app ships its **own** auth controllers and Vue pages (`app/Http/Controllers/Auth/`, `resources/js/pages/auth/`) rather than Fortify's built-in views. Fortify provides the action pipeline (login, registration, password reset, 2FA, password confirmation).

#### **Features**
- Registration, login, password reset, password confirmation
- Email verification (via `MustVerifyEmail` + the `verified` middleware; skippable in local dev with `VERIFY_EMAIL=false`, see Getting Started)
- Two-factor authentication (2FA): QR code, recovery codes, challenge verification, custom Vue components

#### **Configuration**
```php
// config/fortify.php
// Most optional features are left commented because the app uses its own
// auth controllers; two-factor is the active Fortify feature.
'features' => [
    Features::twoFactorAuthentication([
        'confirm' => true,
        'confirmPassword' => true,
    ]),
],
```

The email-verification toggle lives in `config/auth.php`:
```php
'verify_email' => (bool) env('VERIFY_EMAIL', true),
```

#### **Error pages**
Production HTTP errors (403 / 404 / 500 / 503) render a branded Inertia page (`resources/js/pages/ErrorPage.vue`) via a `respond()` interceptor in `bootstrap/app.php`, gated on `! app()->hasDebugModeEnabled()` so development still shows the real exception.

---

## 🧪 Testing

### Running Tests

```bash
# Backend (PHPUnit): all suites (Unit / Feature / Integration)
php artisan test

# Backend: a specific file or filter
php artisan test tests/Feature/Http/Controllers/Goals/GoalControllerTest.php
php artisan test --filter=GoalControllerTest

# Frontend (Vitest)
LARAVEL_BYPASS_ENV_CHECK=1 npx vitest --run

# Static analysis (Larastan / PHPStan, level 3)
./vendor/bin/phpstan analyse --memory-limit=512M
```

### Test Structure

```
tests/
├── Feature/              # Feature tests, mirror app/ structure
│   ├── Auth/            # Authentication flows
│   ├── Http/Controllers/ # Controller tests (e.g. Goals/GoalControllerTest.php)
│   ├── Localization/    # i18n guards (translation parity, default locale)
│   └── Settings/
├── Integration/         # Cross-layer flow tests
├── Unit/                # Isolated unit tests
└── js/                  # Vitest frontend tests (components/, pages/)
```

### Example Test

```php
// tests/Feature/Goals/GoalTest.php
namespace Tests\Feature\Goals;

use App\Models\User;
use App\Models\Goal;
use Tests\TestCase;

class GoalTest extends TestCase
{
    public function test_user_can_view_their_goals(): void
    {
        $user = User::factory()->create();
        $goal = Goal::factory()->for($user)->create();
        
        $response = $this->actingAs($user)->get(route('goals.index'));
        
        $response->assertOk();
        $response->assertInertia(fn ($page) => 
            $page->component('Goals/Index')
                ->has('goals', 1)
                ->where('goals.0.id', $goal->id)
        );
    }
    
    public function test_user_cannot_view_others_goals(): void
    {
        $user = User::factory()->create();
        $otherGoal = Goal::factory()->create();
        
        $response = $this->actingAs($user)
            ->get(route('goals.show', $otherGoal));
        
        $response->assertForbidden();
    }
}
```

### Testing Inertia Responses

```php
use Inertia\Testing\AssertableInertia as Assert;

$response->assertInertia(fn (Assert $page) => 
    $page
        ->component('Goals/Index')
        ->has('goals')
        ->has('goals.0', fn (Assert $page) => 
            $page->where('id', 1)
                ->where('title', 'Run 500km')
                ->etc()
        )
);
```

### CI/CD (GitHub Actions)

A single workflow `.github/workflows/ci.yml` runs on push and pull requests: lint (Pint + ESLint + Prettier), PHPUnit, and Vitest, against a PostgreSQL service container for parity with local and production.

---

## 📏 Code Style & Conventions

### PHP

#### **Laravel Pint**
Automatic code formatting following Laravel conventions:

```bash
# Format all files
./vendor/bin/pint

# Check without modifying
./vendor/bin/pint --test

# Format specific file/directory
./vendor/bin/pint app/Models
```

Configuration: `pint.json` (if exists, otherwise uses defaults)

#### **Conventions**
- Use type hints for parameters and return types
- Use property promotion in constructors
- Prefer named routes over URL strings
- Use route model binding when possible
- Keep controllers thin, move logic to models/services
- Use Form Requests for validation

### JavaScript/TypeScript

#### **ESLint + Prettier**
Linting and formatting configuration:

```bash
# Lint files
npm run lint

# Format files
npm run format

# Fix auto-fixable issues
npm run lint:fix
```

Configuration:
- `eslint.config.js` - ESLint rules
- `.prettierrc` - Prettier formatting

#### **Conventions**
- Use `<script setup>` syntax for Vue components
- Use Composition API, not Options API
- Prefer `const` over `let`, never use `var`
- Use TypeScript types for all props and functions
- Use single quotes for strings
- Use 4 spaces for indentation (to match Laravel)
- Use named exports, not default exports
- Organize imports: external packages → internal modules → types

### Vue Components

#### **File Naming**
- **Components**: PascalCase (e.g., `GoalCard.vue`, `AppSidebar.vue`)
- **Pages**: PascalCase (e.g., `Index.vue`, `Create.vue`)
- **Composables**: camelCase with `use` prefix (e.g., `useGoals.ts`)

#### **Component Structure**

```vue
<script setup lang="ts">
// 1. Imports
import { ref } from 'vue';
import { Goal } from '@/types/models';

// 2. Props
defineProps<{
    goal: Goal;
}>();

// 3. Emits (if any)
const emit = defineEmits<{
    update: [value: string];
}>();

// 4. Composables
const appearance = useAppearance();

// 5. Reactive state
const isOpen = ref(false);

// 6. Computed properties
const progressPercentage = computed(() => {
    // ...
});

// 7. Methods
function handleClick() {
    // ...
}

// 8. Lifecycle hooks
onMounted(() => {
    // ...
});
</script>

<template>
    <!-- Template -->
</template>

<style scoped>
/* Scoped styles (if needed, prefer Tailwind) */
</style>
```

### Git Workflow

#### **Branch Naming**
- `feature/goal-filtering` - New features
- `bugfix/goal-card-overflow` - Bug fixes
- `refactor/goal-controller` - Code improvements
- `docs/readme-update` - Documentation

#### **Commit Messages**
Follow conventional commits:

```
feat: add goal filtering by category
fix: resolve goal card overflow issue
refactor: extract goal progress calculation
docs: update developer README
test: add goal entry tests
chore: update dependencies
```

#### **Pull Request Process**
1. Create feature branch from `main`
2. Make changes with descriptive commits
3. Run tests and linters locally
4. Push and create PR
5. Wait for CI checks to pass
6. Request review
7. Address feedback
8. Squash and merge

---

## 🔧 Common Tasks

### Adding a New Page

1. **Create Vue component**
   ```bash
   touch resources/js/pages/Goals/Archive.vue
   ```

2. **Define route**
   ```php
   // routes/goals.php
   Route::get('/goals/archive', [GoalController::class, 'archive'])
       ->name('goals.archive');
   ```

3. **Create controller method**
   ```php
   // app/Http/Controllers/Goals/GoalController.php
   public function archive()
   {
       return Inertia::render('Goals/Archive', [
           'goals' => auth()->user()->goals()
               ->where('status', 'completed')
               ->get(),
       ]);
   }
   ```

4. **Wayfinder auto-generates the route helper**
   ```typescript
   // Use in components
   import { goals } from '@/routes';
   
   goals.archive() // → /goals/archive
   ```

### Adding a shadcn-vue Component

```bash
# Install a new component
npx shadcn-vue@latest add button

# Install multiple components
npx shadcn-vue@latest add card dialog dropdown-menu
```

Components are added to `resources/js/components/ui/`

### Adding or Editing Translations

UI strings are localized (English + French). Laravel `lang/` files are the single source of truth, bridged to Vue by `laravel-vue-i18n`.

- Add keys to both `lang/en/<domain>.php` and `lang/fr/<domain>.php` (semantic dotted keys, e.g. `goals.actions.create`).
- Use them in Vue via `$t('goals.actions.create')` (or `trans()` in `<script setup>`).
- Every `en` key must have an `fr` counterpart in both directions - the `TranslationParityTest` enforces this.
- No em-dashes in any translation value (project style rule).

See `CONTRIBUTING.md` for the full translation workflow.

### Creating a Custom Composable

```typescript
// resources/js/composables/useGoals.ts
import { ref, computed } from 'vue';
import { Goal } from '@/types/models';

export function useGoals(initialGoals: Goal[]) {
    const goals = ref<Goal[]>(initialGoals);
    
    const activeGoals = computed(() => 
        goals.value.filter(g => g.status === 'in_progress')
    );
    
    const completedGoals = computed(() =>
        goals.value.filter(g => g.status === 'completed')
    );
    
    function addGoal(goal: Goal) {
        goals.value.push(goal);
    }
    
    return {
        goals,
        activeGoals,
        completedGoals,
        addGoal,
    };
}
```

Usage:
```vue
<script setup lang="ts">
import { useGoals } from '@/composables/useGoals';

const props = defineProps<{ goals: Goal[] }>();
const { activeGoals, completedGoals, addGoal } = useGoals(props.goals);
</script>
```

### Updating TypeScript Types

When you modify a model structure:

1. **Update migration and model**
2. **Update TypeScript interface**
   ```typescript
   // resources/js/types/models.d.ts
   export interface Goal {
       // Add new field
       estimated_hours: number | null;
       // ...
   }
   ```
3. **Update factory (if seeding)**
4. **Update any components using the model**

### Database Operations

```bash
# Create migration
php artisan make:migration add_column_to_goals_table

# Rollback last migration
php artisan migrate:rollback

# Rollback all migrations
php artisan migrate:reset

# Fresh migration (drops all tables)
php artisan migrate:fresh

# Fresh migration + seed
php artisan migrate:fresh --seed

# Create model + migration + factory + seeder
php artisan make:model Project -mfs
```

### Cache Management

```bash
# Clear all caches
php artisan optimize:clear

# Or individually:
php artisan cache:clear        # Application cache
php artisan config:clear       # Config cache
php artisan route:clear        # Route cache
php artisan view:clear         # Compiled views

# Optimize for production
php artisan optimize
```

### Asset Management

```bash
# Development (with HMR)
run dev

# Production build
npm run build

# Build for SSR (if using server-side rendering)
npm run build -- --ssr

# Clear Vite cache
rm -rf node_modules/.vite
```

---

## 🐛 Troubleshooting

### Common Issues

#### **Issue: Inertia page not found**

**Symptom**: 404 error or "Inertia page component not found"

**Solution**:
```bash
# Check if page component exists
ls resources/js/pages/Goals/Index.vue

# Ensure controller returns correct component name
// Should match file path without .vue extension
return Inertia::render('Goals/Index');

# Clear Vite cache
rm -rf node_modules/.vite
npm run dev
```

#### **Issue: TypeScript errors with route helpers**

**Symptom**: `goals.index()` shows TypeScript error

**Solution**:
```bash
# Regenerate Wayfinder routes
php artisan wayfinder:generate

# Or restart Vite dev server (it auto-generates)
npm run dev
```

#### **Issue: Props not showing in Vue component**

**Symptom**: Props are `undefined` in component

**Solution**:
```typescript
// Check prop definition matches controller data
// Controller:
return Inertia::render('Goals/Index', [
    'goals' => $goals, // ✅
]);

// Component:
defineProps<{
    goals: Goal[]; // ✅ Must match key name
}>();
```

#### **Issue: CSRF token mismatch**

**Symptom**: 419 error on form submission

**Solution**:
```bash
# Clear sessions
php artisan session:flush

# Check .env has correct APP_URL
APP_URL=http://localhost:8000

# Ensure cookies work in browser (not private mode)
```

#### **Issue: Vite HMR not working**

**Symptom**: Changes not reflected without refresh

**Solution**:
```bash
# Check Vite is running
npm run dev

# Ensure APP_URL in .env matches your domain
APP_URL=http://localhost:8000

# Clear browser cache and restart Vite
```

#### **Issue: Database connection failed**

**Symptom**: `SQLSTATE[08006]` / connection refused

**Solution**:
```bash
# Check PostgreSQL is running and reachable
psql -U postgres -c "\l"

# Verify .env credentials
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=ignite
DB_USERNAME=postgres
DB_PASSWORD=your_password

# On Windows, ensure pdo_pgsql is enabled in php.ini

# Test connection
php artisan db:show
```

#### **Issue: Migration fails**

**Symptom**: Foreign key constraint errors

**Solution**:
```bash
# Check migration order - dependencies must run first
# Example: categories must exist before goals

# Rollback and re-run
php artisan migrate:rollback
php artisan migrate

# Or fresh start
php artisan migrate:fresh
```

#### **Issue: Dark mode not persisting**

**Symptom**: Dark mode resets on page reload

**Solution**:
```typescript
// Check localStorage is accessible
localStorage.getItem('appearance')

// Ensure useAppearance composable is called
import { useAppearance } from '@/composables/useAppearance';
const appearance = useAppearance();
```

#### **Issue: shadcn-vue component styling broken**

**Symptom**: Components look unstyled

**Solution**:
```bash
# Ensure Tailwind is compiling
npm run dev

# Check app.css is imported in app.ts
import '@/css/app.css';

# Reinstall component
npx shadcn-vue@latest add button
```

#### **Issue: Form validation errors not showing**

**Symptom**: Validation fails but no error messages

**Solution**:
```vue
<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';

const form = useForm({...});

// Check v-slot exposes errors
</script>

<template>
    <form @submit.prevent="form.post(...)">
        <input v-model="form.title" />
        
        <!-- Access errors from form object -->
        <p v-if="form.errors.title">{{ form.errors.title }}</p>
    </form>
</template>
```

#### **Issue: Two-factor authentication not working**

**Symptom**: QR code not generating or recovery codes missing

**Solution**:
```bash
# Ensure 2FA feature is enabled
// config/fortify.php
Features::twoFactorAuthentication([...])

# Check user has 2FA enabled
$user->two_factor_secret // Should not be null

# Regenerate recovery codes
php artisan tinker
$user = User::find(1);
$user->replaceRecoveryCodes();
```

### Performance Issues

#### **Slow Page Loads**

**Solutions**:
```php
// Eager load relationships to avoid N+1 queries
$goals = Goal::with(['category', 'entries', 'milestones'])->get();

// Use pagination for large datasets
$goals = Goal::paginate(20);

// Add database indexes
Schema::table('goals', function (Blueprint $table) {
    $table->index(['user_id', 'status']);
});
```

#### **Large Bundle Size**

**Solutions**:
```bash
# Analyze bundle
npm run build -- --report

# Code-split heavy components
// Use dynamic imports
const HeavyComponent = defineAsyncComponent(() => 
    import('./components/HeavyComponent.vue')
);

# Tree-shake unused Tailwind classes (already configured)
```

### Debugging Tools

#### **Laravel Debugbar** (optional, for development)

```bash
composer require barryvdh/laravel-debugbar --dev
```

Shows queries, performance, and request data at bottom of page.

#### **Laravel Telescope** (optional, for advanced debugging)

```bash
composer require laravel/telescope --dev
php artisan telescope:install
php artisan migrate
```

Visit `/telescope` for detailed application insights.

#### **Vue DevTools**

Install browser extension:
- [Chrome](https://chrome.google.com/webstore/detail/vuejs-devtools/)
- [Firefox](https://addons.mozilla.org/en-US/firefox/addon/vue-js-devtools/)

Inspect component state, props, and Inertia data.

#### **Inertia DevTools**

Included in Vue DevTools - shows Inertia visits and page data.

---

## 📚 Additional Resources

### Official Documentation

- **Laravel**: https://laravel.com/docs
- **Vue 3**: https://vuejs.org/guide/introduction.html
- **Inertia.js**: https://inertiajs.com
- **Fortify**: https://laravel.com/docs/fortify
- **Wayfinder**: https://github.com/lepikhinb/laravel-wayfinder
- **shadcn-vue**: https://www.shadcn-vue.com
- **Tailwind CSS**: https://tailwindcss.com/docs
- **Vite**: https://vitejs.dev

### Learning Resources

#### **Inertia.js**
- [Inertia.js Crash Course](https://laracasts.com/series/build-modern-laravel-apps-using-inertia-js) (Laracasts)
- [Official Examples](https://github.com/inertiajs/inertia/tree/master/examples)

#### **Vue 3 Composition API**
- [Vue 3 Essentials](https://vuejs.org/guide/essentials/application.html)
- [Composition API FAQ](https://vuejs.org/guide/extras/composition-api-faq.html)
- [Vue 3 Cheat Sheet](https://vue-cheatsheet.themeselection.com/)

#### **TypeScript**
- [TypeScript Handbook](https://www.typescriptlang.org/docs/handbook/intro.html)
- [TypeScript in 5 Minutes](https://www.typescriptlang.org/docs/handbook/typescript-in-5-minutes.html)

#### **Laravel**
- [Laracasts](https://laracasts.com) - Video tutorials
- [Laravel News](https://laravel-news.com) - Latest updates
- [Laravel Daily](https://laraveldaily.com) - Tips and tricks

### Community & Support

- **Discord**: [Laravel Discord](https://discord.gg/laravel)
- **Forum**: [Laracasts Forum](https://laracasts.com/discuss)
- **Stack Overflow**: Tag questions with `laravel`, `vue.js`, `inertia.js`

---

## 🗂️ Project Structure Reference

### Key Files & Their Purpose

```
.
├── .env.example              # Environment template
├── artisan                   # CLI tool for Laravel commands
├── composer.json             # PHP dependencies
├── package.json              # JavaScript dependencies
├── vite.config.ts            # Vite build configuration
├── tsconfig.json             # TypeScript configuration
├── tailwind.config.js        # Tailwind CSS configuration
├── components.json           # shadcn-vue configuration
├── eslint.config.js          # ESLint rules
├── .prettierrc               # Prettier formatting rules
│
├── app/
│   ├── Http/
│   │   ├── Controllers/      # Handle HTTP requests, return Inertia responses
│   │   ├── Middleware/       # Request/response filtering
│   │   └── Requests/         # Form validation logic
│   ├── Models/               # Eloquent ORM models (database entities)
│   └── Providers/            # Service container bindings
│
├── config/                   # Laravel configuration files
│   ├── fortify.php           # Authentication features
│   ├── inertia.php           # Inertia.js settings
│   └── database.php          # Database connections
│
├── database/
│   ├── migrations/           # Database schema definitions
│   ├── factories/            # Model factories for testing/seeding
│   └── seeders/              # Database seeders for demo data
│
├── resources/
│   ├── css/
│   │   └── app.css           # Global styles + Tailwind imports
│   ├── js/
│   │   ├── app.ts            # Main Vue app initialization
│   │   ├── components/       # Reusable Vue components
│   │   ├── composables/      # Shared reactive logic
│   │   ├── layouts/          # Page layout templates
│   │   ├── pages/            # Inertia page components
│   │   ├── routes/           # Auto-generated route helpers
│   │   ├── types/            # TypeScript type definitions
│   │   └── lib/              # Utility functions
│   └── views/
│       └── app.blade.php     # Root HTML template
│
├── routes/
│   ├── web.php               # Public + dashboard routes
│   ├── auth.php              # Authentication routes
│   ├── goals.php             # Goal feature routes
│   └── settings.php          # Settings routes
│
├── tests/
│   ├── Feature/              # Integration tests (full request/response)
│   └── Unit/                 # Unit tests (isolated logic)
│
└── public/                   # Publicly accessible files (assets)
    └── build/                # Compiled assets (generated by Vite)
```

### Environment Variables Reference

```env
# Application
APP_NAME=Ignite
APP_ENV=local                 # local | production
APP_DEBUG=true                # true for development; false triggers the branded error pages
APP_URL=http://localhost:8000

# Auth (local dev convenience)
VERIFY_EMAIL=false            # false skips email verification locally; defaults to true (prod stays enforced)

# Database (PostgreSQL)
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=ignite
DB_USERNAME=postgres
DB_PASSWORD=

# Mail (optional, for email features)
MAIL_MAILER=log               # log | smtp | mailgun
MAIL_FROM_ADDRESS=noreply@ignite.test
MAIL_FROM_NAME="${APP_NAME}"

# Session
SESSION_DRIVER=database       # database | file | cookie
SESSION_LIFETIME=120

# Cache
CACHE_DRIVER=database         # database | file | redis

# Queue
QUEUE_CONNECTION=sync         # sync | database | redis

# Vite
VITE_APP_NAME="${APP_NAME}"
```

---

## 🚀 Deployment Checklist

### Pre-Deployment

- [ ] Run all tests: `php artisan test`
- [ ] Run linters: `npm run lint` and `./vendor/bin/pint`
- [ ] Build production assets: `npm run build`
- [ ] Set `APP_ENV=production` in `.env`
- [ ] Set `APP_DEBUG=false` in `.env`
- [ ] Generate new `APP_KEY`: `php artisan key:generate`
- [ ] Configure production database (PostgreSQL) credentials
- [ ] Set up a mail driver if sending email (SMTP, Mailgun, etc.); defaults to `log`

> Session, cache, and queue all default to the database driver, so a basic instance needs no Redis and no separate queue worker.

### Manual deploy (non-container)

The supported path is the container in **Deployment (self-hosting)** below. For a manual deploy on a plain server without Docker:

```bash
# 1. Pull latest code
git pull origin main

# 2. Install dependencies
composer install --optimize-autoloader --no-dev
npm ci

# 3. Build assets
npm run build

# 4. Run migrations
php artisan migrate --force

# 5. Optimize Laravel
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 6. Restart queue workers (only if you switched QUEUE_CONNECTION off sync)
php artisan queue:restart

# 7. Set correct permissions (adjust the user to your web server)
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### Post-Deployment

- [ ] Test critical user flows (login, create goal, etc.)
- [ ] Check application logs (stdout/stderr on a container platform, or `storage/logs/`)
- [ ] Monitor performance and database queries
- [ ] Set up monitoring (Laravel Telescope, Sentry, etc.)
- [ ] Configure automated backups

### Deployment (self-hosting)

Ignite is self-hostable and ships a production container that runs on any Docker host (a VPS, a PaaS, your own cluster).

**What a production instance needs**
- **App server:** a single FrankenPHP container (Caddy + PHP fused) built from `docker/production/frankenphp/Dockerfile`. It serves HTTP on `$PORT` and expects TLS to be terminated in front of it (by your proxy, load balancer, or PaaS edge).
- **Database:** PostgreSQL.
- All state lives in Postgres: sessions, cache, and the queue (`QUEUE_CONNECTION=sync`, so a basic instance needs no separate worker).

**Required env** (never commit secrets): `APP_KEY` (`php artisan key:generate --show`), `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL`, `PORT`, the `DB_*` block, `SESSION_DRIVER=database`, `SESSION_SECURE_COOKIE=true` (behind TLS), `CACHE_STORE=database`, `QUEUE_CONNECTION=sync`, `VERIFY_EMAIL`, `SUPPORT_EMAIL`, `MAIL_MAILER`, `LOG_CHANNEL=stderr`.

**On deploy:** run `php artisan migrate --force` as a release step, then warm caches with `config:cache` / `route:cache` / `view:cache`. The provided container entrypoint warms caches at startup automatically.

**Behind a TLS-terminating proxy:** the app trusts forwarded headers (`trustProxies` in `bootstrap/app.php`) so HTTPS is detected correctly, and the bundled `Caddyfile` disables Caddy's own auto-HTTPS since your proxy handles TLS.

> A ready-made `railway.json` is included for those deploying to Railway (the platform the maintainers' own hosted instance uses), but nothing about the app is Railway-specific.

---

## 🤝 Contributing

### Getting Started

1. Fork the repository
2. Clone your fork: `git clone <your-fork-url>`
3. Create a feature branch: `git checkout -b feature/your-feature`
4. Make your changes
5. Run tests: `php artisan test`
6. Run linters: `npm run lint && ./vendor/bin/pint`
7. Commit changes: `git commit -m "feat: add your feature"`
8. Push to your fork: `git push origin feature/your-feature`
9. Open a Pull Request

### Development Guidelines

- Follow existing code style and conventions
- Write tests for new features
- Update documentation if needed
- Keep PRs focused on a single feature/fix
- Add meaningful commit messages
- Ensure CI checks pass before requesting review

### Code Review Process

1. Automated checks run on push (tests, linting)
2. At least one approval required from maintainers
3. Address review feedback
4. Squash commits before merging
5. Delete branch after merge

---

## 📝 Notes

### Important Conventions

#### **DO NOT EDIT** Auto-Generated Files
- `resources/js/actions/` - Wayfinder actions
- `resources/js/routes/` - Wayfinder route helpers
- `resources/js/wayfinder/` - Wayfinder config

These are regenerated automatically when Laravel routes change.

#### **Model Casting**
When defining models, always cast attributes:

```php
protected $casts = [
    'target_value' => 'decimal:2',
    'is_completed' => 'boolean',
    'completed_at' => 'datetime',
    'criteria' => 'array', // JSON column
];
```

This ensures consistent data types in both PHP and TypeScript.

#### **Mass Assignment Protection**
Always define `$fillable` or `$guarded` on models:

```php
protected $fillable = [
    'title',
    'description',
    'type',
    // ...
];
```

#### **N+1 Query Prevention**
Always eager load relationships:

```php
// ❌ Bad - triggers N+1 queries
$goals = Goal::all();
foreach ($goals as $goal) {
    echo $goal->category->name; // Query per goal
}

// ✅ Good - single query with join
$goals = Goal::with('category')->get();
foreach ($goals as $goal) {
    echo $goal->category->name;
}
```

### Architecture Decisions

#### **Why Inertia.js?**
- Eliminates need for separate API
- Server-side routing with SPA experience
- Simpler than building full REST/GraphQL API
- Great for team with both Laravel and Vue experience

#### **Why TypeScript?**
- Catch errors at compile-time, not runtime
- Better IDE autocomplete and IntelliSense
- Self-documenting code with type hints
- Safer refactoring

#### **Why shadcn-vue?**
- Accessible by default (ARIA, keyboard navigation)
- Unstyled - full control over appearance
- Copy/paste approach - no hidden dependencies
- Built on Radix Vue primitives

#### **Why Wayfinder?**
- Type-safe routes eliminate typos
- Autocomplete for route names and parameters
- Automatically syncs with Laravel routes
- Easier refactoring when routes change

### Future Enhancements

**Planned features:**
- [ ] Real-time notifications (Laravel Echo + Pusher)
- [ ] Goal templates library
- [ ] Social features (share goals, follow users)
- [ ] Mobile app (React Native or Flutter)
- [ ] Advanced analytics dashboard
- [ ] Integrations (Google Calendar, Strava, etc.)
- [ ] AI suggestions for goal setting
- [ ] Collaborative goals (team goals)
- [ ] Gamification leaderboards
- [ ] Export/import data (CSV, JSON)

**Technical debt:**
- [ ] Add end-to-end tests (Cypress/Playwright)
- [ ] Implement API for mobile apps
- [ ] Add Redis for caching and queues
- [ ] Set up Laravel Horizon for queue monitoring
- [ ] Implement comprehensive error tracking (Sentry)
- [ ] Add performance monitoring (New Relic)
- [ ] Implement rate limiting for API routes
- [ ] Add database query performance monitoring

---

## 📞 Support

### Getting Help

1. **Check Documentation**: Read relevant docs first
2. **Search Issues**: Check if someone else had the same problem
3. **Ask the Team**: Reach out in team chat/Discord
4. **Create Issue**: Open a detailed issue on GitHub

### Reporting Bugs

Include:
- **Description**: What happened vs. what you expected
- **Steps to Reproduce**: Exact steps to trigger the bug
- **Environment**: PHP version, Node version, OS
- **Logs**: Relevant error messages from logs
- **Screenshots**: If UI-related

### Requesting Features

Include:
- **Use Case**: Why is this feature needed?
- **Proposed Solution**: How should it work?
- **Alternatives**: Other approaches you considered
- **Impact**: Who benefits from this feature?

---

## 📄 License

This project is open-source and available under the [MIT License](LICENSE).

---

## 🙏 Acknowledgments

- **Laravel Team** - For the amazing framework
- **Inertia.js Team** - For bridging Laravel and Vue seamlessly
- **shadcn** - For the beautiful component library
- **Tailwind Labs** - For Tailwind CSS
- **Vue.js Team** - For the progressive framework

---

**Happy Coding! 🔥**

For questions or suggestions about this documentation, please open an issue or reach out to the team.

---

*Last updated: 2026-07-12*
