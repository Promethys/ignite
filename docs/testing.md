# Testing

This page covers the testing infrastructure, conventions, and tools used in Ignite.

## Overview

### Testing stack

| Layer                      | Tool                                                           |
| -------------------------- | -------------------------------------------------------------- |
| Backend test framework     | PHPUnit (via Laravel)                                          |
| Backend database           | SQLite in-memory (fast, isolated)                              |
| Frontend test runner       | Vitest                                                         |
| Frontend component testing | `@vue/test-utils`                                              |
| Static analysis            | Larastan / PHPStan (level 3) for PHP, `vue-tsc` for TypeScript |
| CI                         | GitHub Actions                                                 |

## Testing conventions

### Test categories

The backend test suite is divided into three categories:

**Unit tests**: `tests/Unit/`

- No Laravel boot, no database
- Pure PHP logic: helpers, value objects, standalone services
- Fast and side-effect-free

**Feature tests**: `tests/Feature/`

- Laravel fully booted, database available
- Mirror the `app/` directory structure
- Cover controllers, models, observers, policies

**Integration tests**: `tests/Integration/`

- Multi-component workflows (e.g. creation, progress, auto-completion)
- Do not map to a single class

### Naming conventions

- All test classes use the `*Test` suffix
- Test method names use snake*case prefixed with `test*`
- Names describe the behavior, not the implementation

```php
// Avoid              // [!code --]
test_goal()           // [!code --]
test_create()         // [!code --]
test_it_works()       // [!code --]

// Preferred                                    // [!code ++]
test_user_can_create_a_goal()                   // [!code ++]
test_guest_is_redirected_to_login()             // [!code ++]
test_progress_percentage_is_never_negative()    // [!code ++]
```

## Backend architecture (PHPUnit)

### Directory structure

Feature tests mirror the `app/` directory structure:

```text
app/
├── Http/Controllers/Goals/GoalController.php
├── Models/Goal.php
└── Observers/GoalObserver.php

tests/
├── Feature/
│   ├── Auth/
│   ├── Settings/
│   ├── Http/
│   │   └── Controllers/
│   │       ├── Goals/
│   │       │   ├── GoalControllerTest.php
│   │       │   └── GoalEntryControllerTest.php
│   │       ├── CategoryControllerTest.php
│   │       └── DashboardControllerTest.php
│   ├── Models/
│   │   ├── GoalTest.php
│   │   ├── GoalEntryTest.php
│   │   ├── CategoryTest.php
│   │   ├── UserTest.php
│   │   └── MilestoneTest.php
│   └── Observers/
│       ├── GoalObserverTest.php
│       └── CategoryObserverTest.php
├── Integration/
│   └── GoalProgressFlowTest.php
├── Unit/
│   └── (pure logic, no DB)
└── TestCase.php
```

### Base test class

All feature tests extend `Tests\TestCase` and use `RefreshDatabase`:

```php
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GoalTest extends TestCase
{
    use RefreshDatabase;

    // ...
}
```

`RefreshDatabase` wraps each test in a transaction and rolls it back, so no manual cleanup is needed. The test database is SQLite in-memory, configured in `phpunit.xml`.

## Frontend architecture (Vitest)

### Directory structure

Frontend tests mirror `resources/js/`:

```text
resources/js/
├── components/goals/BaseGoalCard.vue
├── components/categories/CategoryFormModal.vue
├── composables/useLocale.ts
├── pages/Goals/Index.vue
└── lib/utils.ts

tests/js/
├── components/
│   ├── goals/
│   │   ├── BaseGoalCardTest.ts
│   │   ├── GoalBadgesTest.ts
│   │   ├── GoalFormTest.ts
│   │   └── progress/RecurringProgressTest.ts
│   ├── milestones/
│   │   ├── MilestoneFormModalTest.ts
│   │   └── TimelineTest.ts
│   ├── categories/
│   │   └── CategoryFormModalTest.ts
│   └── ui/
│       ├── HelpTooltipTest.ts
│       ├── PasswordInputTest.ts
│       └── StatusDotTest.ts
├── composables/
│   ├── useChartThemeTest.ts
│   └── useLocaleTest.ts
├── pages/
│   ├── CategoriesIndexTest.ts
│   ├── ErrorPageTest.ts
│   ├── GoalsIndexTest.ts
│   └── GoalsShowTest.ts
├── lib/
│   ├── chartThemeTest.ts
│   ├── chartUtilsTest.ts
│   ├── i18nBootTest.ts
│   ├── momentLocaleTest.ts
│   └── utilsTest.ts
└── setup.ts
```

### Test types

**Component tests** mount a Vue component in isolation and assert its rendered output and behavior based on props.

**Page tests** mount an Inertia page component from `resources/js/pages/` and assert what it renders from its props. Pages depend on Inertia's router and head manager, so those are stubbed rather than avoided (see "Page tests" below).

**Composable tests** exercise the composition functions in `resources/js/composables/`.

**Utility tests** exercise pure TypeScript functions (e.g. `getDateDiffFromNow`) without mounting any component.

Every page and component added to the app is expected to carry a test. Backend controller tests assert that the correct Inertia component and props are returned; the page test asserts what the page does with those props. The two are complementary, not alternatives.

## Running tests

### Backend

```bash
# Run all tests
php artisan test

# Run a specific testsuite
php artisan test --testsuite=Feature
php artisan test --testsuite=Unit
php artisan test --testsuite=Integration

# Run a specific file
php artisan test tests/Feature/Models/GoalTest.php

# Run a specific method
php artisan test --filter=test_progress_percentage_for_ascending_goal

# Stop on first failure
php artisan test --stop-on-failure

# Run with coverage (requires Xdebug)
php artisan test --coverage
```

`composer test` runs `php artisan config:clear` followed by `php artisan test`.

::: tip Coverage inside the dev container
The development image defaults Xdebug to `develop,debug`, which excludes the coverage driver, and `--coverage` reports that none is available. Xdebug reads `XDEBUG_MODE` at run time, so enable it for the single command:

```bash
docker compose -f compose.dev.yaml exec -e XDEBUG_MODE=coverage web php artisan test --coverage
```

:::

### Frontend

```bash
# Run all frontend tests once
npm run test:js

# Watch mode
npm run test:js:watch

# Run a specific file
npx vitest tests/js/components/goals/QuantifiableGoalCardTest.ts

# With coverage
npx vitest --coverage
```

`npm run test:js` maps to `vitest run` in `package.json`. CI (and any environment without a real `.env`/`APP_KEY` already set up) runs it as:

```bash
LARAVEL_BYPASS_ENV_CHECK=1 npx vitest --run
```

The `LARAVEL_BYPASS_ENV_CHECK` flag skips the Laravel Vite plugin's environment checks, which otherwise expect a valid `.env` with `APP_KEY` set.

### Static analysis

```bash
./vendor/bin/phpstan analyse --memory-limit=512M
```

`composer check` runs Pint (formatting check), PHPStan, and the full backend test suite in sequence.

The frontend equivalent is `npm run check`, which runs ESLint, Prettier, `vue-tsc` and Vitest. Every one of them only reports. To rewrite files instead, run `npm run fix`, which applies ESLint's auto-fixes and then Prettier. The same split applies to the individual scripts: `lint` and `format` report, `lint:fix` and `format:fix` write.

```bash
npm run typecheck
```

Type errors are invisible to the build, since Vite strips types without checking them, so `vue-tsc` is the only thing that catches them outside an editor.

::: warning
Do not run the full backend or frontend suite speculatively during day-to-day work; run only the tests you created or changed, or the ones in the same group/folder. Let CI run the full suite.
:::

## Writing backend tests

### Model tests

Model tests live in `tests/Feature/Models/` and verify relationships, casts, accessors, and methods. Organize with section headers:

```php
class GoalTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // RELATIONSHIP TESTS
    // =========================================================================

    // =========================================================================
    // CAST TESTS
    // =========================================================================

    // =========================================================================
    // ACCESSOR TESTS
    // =========================================================================

    // =========================================================================
    // METHOD TESTS
    // =========================================================================
}
```

#### Relationship tests

For each relationship, verify:

1. The FK record exists in the database
2. The relationship method returns the correct model type

```php
// BelongsTo
public function test_goal_belongs_to_user()
{
    $user = User::factory()->create();
    $goal = Goal::factory()->create(['user_id' => $user->id]);

    $this->assertDatabaseHas('goals', ['user_id' => $user->id]);
    $this->assertInstanceOf(User::class, $goal->user);
    $this->assertEquals($user->id, $goal->user->id);
}

// HasMany (test via inverse)
public function test_goal_has_many_entries()
{
    $goal = Goal::factory()->create();
    $entry = GoalEntry::factory()->create(['goal_id' => $goal->id]);

    $this->assertDatabaseHas('goal_entries', ['goal_id' => $goal->id]);
    $this->assertCount(1, $goal->entries);
    $this->assertInstanceOf(GoalEntry::class, $goal->entries->first());
}
```

#### Cast tests

```php
public function test_goal_casts_current_value_as_decimal()
{
    $goal = Goal::factory()->create(['current_value' => 10.5]);

    $this->assertIsFloat($goal->current_value); // or assertIsString if cast to decimal string
    $this->assertEquals(10.5, $goal->current_value);
}
```

#### Accessor tests

```php
public function test_progress_percentage_for_ascending_goal()
{
    $goal = Goal::factory()->create([
        'type' => 'quantifiable',
        'direction' => 'ascending',
        'initial_value' => 0,
        'current_value' => 50,
        'target_value' => 100,
    ]);

    $this->assertEquals(50, $goal->progress_percentage);
}
```

#### Common pitfalls

Never use hardcoded IDs for foreign keys:

```php
Goal::factory()->create(['user_id' => 999]);    // [!code error]

// Always create the related record             // [!code ++]
$user = User::factory()->create();              // [!code ++]
Goal::factory()->create(['user_id' => $user->id]);  // [!code ++]
```

Observer vs factory: factories bypass observers by default in some configurations. To test observer logic, use `Model::create()` directly instead of factories.

### Controller tests

Controller tests live in `tests/Feature/Http/Controllers/`, mirroring `app/Http/Controllers/`.

Every controller test must cover:

- **Authorization**: guest redirect, ownership enforcement
- **Happy path**: successful action with correct DB state and redirect
- **Validation**: required fields, type constraints

```php
class GoalControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login()
    {
        $response = $this->get(route('goals.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_user_can_create_a_goal()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('goals.store'), [
            'title' => 'Run a marathon',
            'type' => 'simple',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('goals', [
            'user_id' => $user->id,
            'title' => 'Run a marathon',
        ]);
    }

    public function test_user_cannot_edit_other_users_goal()
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $goal = Goal::factory()->create(['user_id' => $owner->id]);

        $response = $this->actingAs($other)->get(route('goals.edit', $goal));

        $response->assertForbidden();
    }
}
```

For Inertia responses, use `assertInertia()`:

```php
$response->assertInertia(fn ($page) =>
    $page->component('Goals/Index')
         ->has('goals')
);
```

### Observer tests

Observer tests live in `tests/Feature/Observers/`. Use `Model::create()` directly (not factories) to exercise the observer lifecycle:

```php
class GoalObserverTest extends TestCase
{
    use RefreshDatabase;

    public function test_ascending_goal_auto_completes_when_target_reached()
    {
        $user = User::factory()->create();
        $goal = Goal::create([
            'user_id' => $user->id,
            'title' => 'Save money',
            'type' => 'quantifiable',
            'direction' => 'ascending',
            'initial_value' => 0,
            'current_value' => 0,
            'target_value' => 1000,
            'status' => 'in_progress',
        ]);

        $goal->update(['current_value' => 1000]);

        $this->assertEquals('completed', $goal->fresh()->status);
        $this->assertNotNull($goal->fresh()->completed_at);
    }
}
```

### MCP tool tests

Tool tests live in `tests/Feature/Mcp/Tools/` and drive a tool through the real server dispatch, so registration, scopes, validation, and authorization all apply:

```php
IgniteServer::tool(ListGoalsTool::class, ['goal_id' => $goal->id])
    ->assertOk();
```

**Authenticate with the abilities you mean to test.** Tool visibility is decided by the token's abilities, so scope tests go through Sanctum:

```php
Sanctum::actingAs($user, ['read', 'write']);
```

Three things about this are easy to get wrong:

- `Sanctum::actingAs($user)` with **no** abilities argument grants an empty set, so `tokenCan()` is false for everything and every tool disappears. Always pass the abilities explicitly.
- `actingAs` does **not** apply the `write` implies `read` normalization that token creation does. A real `write` token stores `['read', 'write']`, so simulate it as `['read', 'write']` or read tools will wrongly appear unavailable.
- To test that an ability is _missing_, pick one that genuinely excludes it. `['delete']` is a good stand-in for "no read access"; `['write']` is not, because a real write token carries read.

**A scope test needs both directions.** Assert that a granted ability works _and_ that a withheld one is refused. A lone negative assertion passes just as well when the mechanism is broken and refuses everything.

**`assertSee` and `assertStructuredContent` check different payloads.** `assertSee` inspects the text content; `assertStructuredContent` inspects the structured JSON. A tool can return correct text while its structured payload is wrong, and clients that prefer structured content would then receive nothing useful. Assert on whichever payload the consumer actually reads, and on both when a tool returns both:

```php
IgniteServer::tool(GetGoalTool::class, ['goal_id' => $goal->id])
    ->assertOk()
    ->assertStructuredContent(fn (AssertableJson $json) => $json
        ->has('entries', 2)
        ->missing('user')
        ->etc());
```

Asserting `missing()` on fields that must never be exposed is worth doing explicitly, since tool output is sent to a third-party AI provider.

## Writing frontend tests

### Setup

Each test file mounts a component using `@vue/test-utils` and asserts on the rendered output:

```ts
import { mount } from '@vue/test-utils';
import { describe, it, expect } from 'vitest';
import QuantifiableGoalCard from '@/components/goals/QuantifiableGoalCard.vue';
import type { Goal } from '@/types/models';

const baseGoal: Goal = {
    id: 1,
    title: 'Save money',
    status: 'in_progress',
    type: 'quantifiable',
    progress_percentage: 50,
    // ... other required fields
};

describe('QuantifiableGoalCard', () => {
    it('shows pause option when goal is in_progress', () => {
        const wrapper = mount(QuantifiableGoalCard, {
            props: { item: { ...baseGoal, status: 'in_progress' } },
        });

        expect(wrapper.text()).toContain('Pause');
        expect(wrapper.text()).not.toContain('Resume');
    });

    it('shows resume option when goal is paused', () => {
        const wrapper = mount(QuantifiableGoalCard, {
            props: { item: { ...baseGoal, status: 'paused' } },
        });

        expect(wrapper.text()).toContain('Resume');
        expect(wrapper.text()).not.toContain('Pause');
    });
});
```

### Page tests

Page components live in `resources/js/pages/` and are mounted the same way as components, with two Inertia dependencies stubbed:

1. **`<Head>`** requires the app head manager, which does not exist in a unit test. Stub only that export and keep the rest of `@inertiajs/vue3` real.
2. **`router`** performs real navigation. Mock the methods the page calls so you can assert against them.

Layout wrappers and any child component that is not under test are replaced with pass-through stubs, so the page's own markup is what gets asserted.

```ts
import CategoriesIndex from '@/pages/Categories/Index.vue';
import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';

// <Head> needs the app head manager, absent in unit tests; stub only that export.
vi.mock('@inertiajs/vue3', async (importOriginal) => {
    const actual = await importOriginal<typeof import('@inertiajs/vue3')>();
    return {
        ...actual,
        Head: { name: 'Head', render: () => null },
        router: { delete: vi.fn() },
    };
});

const passthrough = (componentNames: string[]) =>
    Object.fromEntries(
        componentNames.map((componentName) => [
            componentName,
            { template: '<div><slot /></div>' },
        ]),
    );

const stubs = passthrough(['AppLayout', 'PageHeader']);

describe('Categories/Index', () => {
    it('renders a row per category', () => {
        const wrapper = mount(CategoriesIndex, {
            props: { categories: [{ id: 1, name: 'Health' }] },
            global: { stubs },
        });

        expect(wrapper.text()).toContain('Health');
    });
});
```

Assert on what the page renders from its props, and on the router calls it triggers. Do not assert on the internals of stubbed children; those belong in that child's own test.

### Utility tests

Pure functions need no component mounting:

```ts
import { describe, it, expect } from 'vitest';
import { getDateDiffFromNow } from '@/lib/utils';

describe('getDateDiffFromNow', () => {
    it('returns 0 for today', () => {
        const today = new Date().toISOString().split('T')[0];
        expect(getDateDiffFromNow(today)).toBe(0);
    });

    it('returns negative for past dates', () => {
        expect(getDateDiffFromNow('2020-01-01')).toBeLessThan(0);
    });
});
```

## Best practices

### Arrange-Act-Assert

Every test follows this pattern:

```php
public function test_user_can_update_their_goal()
{
    // Arrange
    $user = User::factory()->create();
    $goal = Goal::factory()->create(['user_id' => $user->id, 'title' => 'Old title']);

    // Act
    $response = $this->actingAs($user)->patch(route('goals.update', $goal), [
        'title' => 'New title',
        'type' => $goal->type,
    ]);

    // Assert
    $response->assertRedirect();
    $this->assertEquals('New title', $goal->fresh()->title);
}
```

### One assertion focus per test

Split separate behaviors into separate tests. A test named `test_user_can_create_a_goal` should not also assert validation errors.

### Use factories, not raw `create()`

```php
Goal::create(['user_id' => 1, 'title' => 'Test', 'type' => 'simple', ...]);  // [!code --]
$goal = Goal::factory()->inProgress()->create(['user_id' => $user->id]);  // [!code ++]
```

Exception: observer tests must use `Model::create()` to trigger the observer.

### Always use named routes

```php
$this->get('/goals');               // [!code --]
$this->get(route('goals.index'));   // [!code ++]
```

## CI/CD integration

Tests run automatically on every push and pull request to `main` via the `ci` GitHub Actions workflow (`.github/workflows/ci.yml`). The pipeline sets up PHP and Node, installs dependencies, then runs Pint, ESLint (`npm run lint`), Prettier (`npm run format`), `vue-tsc` (`npm run typecheck`) and PHPStan, builds the frontend assets and the docs site, and finishes with the backend suite (`./vendor/bin/phpunit`) and the frontend suite (`LARAVEL_BYPASS_ENV_CHECK=1 npx vitest --run`).

The type check is preceded by `php artisan wayfinder:generate --with-form`. The route and action helpers under `resources/js/routes` and `resources/js/actions` are generated and not committed, so on a fresh clone they do not exist until something creates them, and `vue-tsc` runs before the asset build that normally would. The `--with-form` flag matches the `formVariants` option set on the Wayfinder plugin in `vite.config.ts`; without it the generated helpers lack the `.form()` variants that several pages import.
