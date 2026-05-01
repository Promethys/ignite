# Testing Guide

This document explains the testing infrastructure, conventions, and tools used in Ignite.

## Table of Contents

- [Testing Guide](#testing-guide)
  - [Table of Contents](#table-of-contents)
  - [Overview](#overview)
    - [Testing Stack](#testing-stack)
  - [Testing Conventions](#testing-conventions)
    - [Test Categories](#test-categories)
    - [Naming Conventions](#naming-conventions)
  - [Backend Architecture (PHPUnit)](#backend-architecture-phpunit)
    - [Directory Structure](#directory-structure)
    - [Base Test Class](#base-test-class)
  - [Frontend Architecture (Vitest)](#frontend-architecture-vitest)
    - [Frontend Directory Structure](#frontend-directory-structure)
    - [Test Types](#test-types)
  - [Running Tests](#running-tests)
    - [Backend](#backend)
    - [Frontend](#frontend)
  - [Writing Backend Tests](#writing-backend-tests)
    - [Model Tests](#model-tests)
      - [Relationship Tests](#relationship-tests)
      - [Cast Tests](#cast-tests)
      - [Accessor Tests](#accessor-tests)
      - [Common Pitfalls](#common-pitfalls)
    - [Controller Tests](#controller-tests)
    - [Observer Tests](#observer-tests)
  - [Writing Frontend Tests](#writing-frontend-tests)
    - [Setup](#setup)
    - [Utility Tests](#utility-tests)
  - [Best Practices](#best-practices)
    - [Arrange-Act-Assert](#arrange-act-assert)
    - [One assertion focus per test](#one-assertion-focus-per-test)
    - [Use factories, not raw `create()`](#use-factories-not-raw-create)
    - [Always use named routes](#always-use-named-routes)
  - [CI/CD Integration](#cicd-integration)

---

## Overview

### Testing Stack

| Layer | Tool |
|---|---|
| Backend test framework | PHPUnit 11.x (via Laravel) |
| Backend database | SQLite in-memory (fast, isolated) |
| Frontend test runner | Vitest |
| Frontend component testing | @vue/test-utils |
| CI | GitHub Actions |

---

## Testing Conventions

### Test Categories

The test suite is divided into three categories:

**Unit tests** — `tests/Unit/`
- No Laravel boot, no database
- Pure PHP logic: helpers, value objects, standalone services
- Fast and side-effect-free

**Feature tests** — `tests/Feature/`
- Laravel fully booted, database available
- Mirror the `app/` directory structure
- Cover controllers, models, observers, policies

**Integration tests** — `tests/Integration/`
- Multi-component workflows (creation → progress → auto-completion)
- Do not map to a single class

### Naming Conventions

- All test classes use the `*Test` suffix
- Test method names use snake_case prefixed with `test_`
- Names describe the behavior, not the implementation

```
// ✅ Good
test_user_can_create_a_goal()
test_guest_is_redirected_to_login()
test_progress_percentage_is_capped_at_100()

// ❌ Bad
test_goal()
test_create()
test_it_works()
```

---

## Backend Architecture (PHPUnit)

### Directory Structure

Feature tests mirror the `app/` directory structure:

```
app/
├── Http/Controllers/Goals/GoalController.php
├── Models/Goal.php
└── Observers/GoalObserver.php

tests/
├── Feature/
│   ├── Auth/                          # Already covered
│   ├── Settings/                      # Already covered
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

### Base Test Class

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

`RefreshDatabase` wraps each test in a transaction and rolls it back — no manual cleanup needed. The database is SQLite in-memory (configured in `phpunit.xml`).

---

## Frontend Architecture (Vitest)

### Frontend Directory Structure

Frontend tests mirror `resources/js/`:

```
resources/js/
├── components/goals/QuantifiableGoalCard.vue
├── components/categories/CategoryFormModal.vue
└── lib/utils.ts

tests/js/
├── components/
│   ├── goals/
│   │   ├── QuantifiableGoalCardTest.ts
│   │   ├── SimpleGoalCardTest.ts
│   │   ├── RecurringGoalCardTest.ts
│   │   ├── MultiStepGoalCardTest.ts
│   │   └── GoalBadgesTest.ts
│   └── categories/
│       └── CategoryFormModalTest.ts
└── lib/
    └── utilsTest.ts
```

### Test Types

**Component tests** — mount a Vue component in isolation and assert its rendered output and behavior based on props.

**Utility tests** — test pure TypeScript functions (e.g., `getDateDiffFromNow`) without mounting any component.

> **Note:** Inertia page components (`resources/js/pages/`) are not tested directly — they depend heavily on Inertia's router and are better covered by backend controller tests asserting the correct Inertia component and props are returned.

---

## Running Tests

### Backend

```bash
# Run all tests
php artisan test

# Run specific testsuite
php artisan test --testsuite=Feature
php artisan test --testsuite=Unit
php artisan test --testsuite=Integration

# Run specific file
php artisan test tests/Feature/Models/GoalTest.php

# Run specific method
php artisan test --filter=test_progress_percentage_for_ascending_goal

# Stop on first failure
php artisan test --stop-on-failure

# Run with coverage (requires Xdebug)
php artisan test --coverage
```

### Frontend

```bash
# Run all frontend tests
npx vitest

# Watch mode
npx vitest --watch

# Run specific file
npx vitest tests/js/components/goals/QuantifiableGoalCardTest.ts

# With coverage
npx vitest --coverage
```

---

## Writing Backend Tests

### Model Tests

Model tests live in `tests/Feature/Models/` and verify relationships, casts, accessors, and methods.

Organize with section headers:

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

#### Relationship Tests

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

#### Cast Tests

```php
public function test_goal_casts_current_value_as_decimal()
{
    $goal = Goal::factory()->create(['current_value' => 10.5]);

    $this->assertIsFloat($goal->current_value); // or assertIsString if cast to decimal string
    $this->assertEquals(10.5, $goal->current_value);
}
```

#### Accessor Tests

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

#### Common Pitfalls

**Never use hardcoded IDs for foreign keys:**

```php
// ❌ Will fail on FK constraint
Goal::factory()->create(['user_id' => 999]);

// ✅ Always create the related record
$user = User::factory()->create();
Goal::factory()->create(['user_id' => $user->id]);
```

**Observer vs Factory:** Factories bypass observers by default in some configurations. To test observer logic, use `Model::create()` directly instead of factories.

---

### Controller Tests

Controller tests live in `tests/Feature/Http/Controllers/` mirroring `app/Http/Controllers/`.

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

---

### Observer Tests

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

---

## Writing Frontend Tests

### Setup

Each test file mounts a component using `@vue/test-utils` and asserts on the rendered output:

```ts
import { mount } from '@vue/test-utils'
import { describe, it, expect } from 'vitest'
import QuantifiableGoalCard from '@/components/goals/QuantifiableGoalCard.vue'
import type { Goal } from '@/types/models'

const baseGoal: Goal = {
    id: 1,
    title: 'Save money',
    status: 'in_progress',
    type: 'quantifiable',
    progress_percentage: 50,
    // ... other required fields
}

describe('QuantifiableGoalCard', () => {
    it('shows pause option when goal is in_progress', () => {
        const wrapper = mount(QuantifiableGoalCard, {
            props: { item: { ...baseGoal, status: 'in_progress' } },
        })

        expect(wrapper.text()).toContain('Pause')
        expect(wrapper.text()).not.toContain('Resume')
    })

    it('shows resume option when goal is paused', () => {
        const wrapper = mount(QuantifiableGoalCard, {
            props: { item: { ...baseGoal, status: 'paused' } },
        })

        expect(wrapper.text()).toContain('Resume')
        expect(wrapper.text()).not.toContain('Pause')
    })
})
```

### Utility Tests

Pure functions need no component mounting:

```ts
import { describe, it, expect } from 'vitest'
import { getDateDiffFromNow } from '@/lib/utils'

describe('getDateDiffFromNow', () => {
    it('returns 0 for today', () => {
        const today = new Date().toISOString().split('T')[0]
        expect(getDateDiffFromNow(today)).toBe(0)
    })

    it('returns negative for past dates', () => {
        expect(getDateDiffFromNow('2020-01-01')).toBeLessThan(0)
    })
})
```

---

## Best Practices

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
// ✅
$goal = Goal::factory()->inProgress()->create(['user_id' => $user->id]);

// ❌
Goal::create(['user_id' => 1, 'title' => 'Test', 'type' => 'simple', ...]);
```

Exception: observer tests must use `Model::create()` to trigger the observer.

### Always use named routes

```php
// ✅
$this->get(route('goals.index'));

// ❌
$this->get('/goals');
```

---

## CI/CD Integration

Tests run automatically on every push and pull request to `main` and `develop` via GitHub Actions.

**Workflow file**: `.github/workflows/tests.yml`

The pipeline:
1. Sets up PHP 8.5 + Node 22
2. Installs dependencies (`composer install`, `npm ci`)
3. Copies `.env.example` → `.env` and generates app key
4. Builds frontend assets (`npm run build`)
5. Runs `./vendor/bin/phpunit`

> Frontend tests (Vitest) are not yet wired into CI — add `npx vitest --run` to the workflow once frontend tests are in place.

---

**Last Updated**: 2026-05-01
