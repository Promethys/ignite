<?php

namespace Tests\Feature\Services\Categories;

use App\Models\Category;
use App\Models\Goal;
use App\Models\User;
use App\Services\Categories\CategoryService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryServiceTest extends TestCase
{
    use RefreshDatabase;

    private CategoryService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(CategoryService::class);
    }

    public function test_list_for_user_returns_only_the_actors_categories(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        Category::factory()->count(2)->create(['user_id' => $owner->id]);
        Category::factory()->create(['user_id' => $other->id]);

        $categories = $this->service->listForUser($owner);

        $this->assertCount(2, $categories);
        $this->assertTrue($categories->every(fn (Category $category) => $category->user_id === $owner->id));
    }

    public function test_list_for_user_counts_the_goals_filed_under_each_category(): void
    {
        $owner = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $owner->id]);

        Goal::factory()->count(3)->create(['user_id' => $owner->id, 'category_id' => $category->id]);
        Goal::factory()->create(['user_id' => $owner->id, 'category_id' => null]);

        $categories = $this->service->listForUser($owner);

        $this->assertSame(3, $categories->first()->goals_count);
    }

    public function test_list_for_user_breaks_the_goal_count_down_by_status(): void
    {
        $owner = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $owner->id]);

        Goal::factory()->count(2)->create([
            'user_id' => $owner->id,
            'category_id' => $category->id,
            'status' => 'in_progress',
        ]);
        Goal::factory()->create([
            'user_id' => $owner->id,
            'category_id' => $category->id,
            'status' => 'completed',
        ]);
        Goal::factory()->create([
            'user_id' => $owner->id,
            'category_id' => $category->id,
            'status' => 'paused',
        ]);

        $found = $this->service->listForUser($owner)->first();

        $this->assertSame(4, $found->goals_count);
        $this->assertSame(2, $found->active_goals_count);
        $this->assertSame(1, $found->completed_goals_count);
    }

    public function test_find_carries_the_same_counts_as_the_list(): void
    {
        $owner = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $owner->id]);

        Goal::factory()->create([
            'user_id' => $owner->id,
            'category_id' => $category->id,
            'status' => 'in_progress',
        ]);
        Goal::factory()->create([
            'user_id' => $owner->id,
            'category_id' => $category->id,
            'status' => 'completed',
        ]);

        $found = $this->service->find($owner, $category->id);

        $this->assertSame(2, $found->goals_count);
        $this->assertSame(1, $found->active_goals_count);
        $this->assertSame(1, $found->completed_goals_count);
    }

    public function test_list_for_user_orders_by_display_order(): void
    {
        $owner = User::factory()->create();

        Category::factory()->create(['user_id' => $owner->id, 'name' => 'Third', 'order' => 3]);
        Category::factory()->create(['user_id' => $owner->id, 'name' => 'First', 'order' => 1]);
        Category::factory()->create(['user_id' => $owner->id, 'name' => 'Second', 'order' => 2]);

        $names = $this->service->listForUser($owner)->pluck('name')->all();

        $this->assertSame(['First', 'Second', 'Third'], $names);
    }

    public function test_find_returns_the_category_with_its_goal_count_for_the_owner(): void
    {
        $owner = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $owner->id]);
        Goal::factory()->count(2)->create(['user_id' => $owner->id, 'category_id' => $category->id]);

        $found = $this->service->find($owner, $category->id);

        $this->assertTrue($found->is($category));
        $this->assertSame(2, $found->goals_count);
    }

    public function test_find_throws_for_a_non_owner(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $owner->id]);

        $this->expectException(AuthorizationException::class);

        $this->service->find($intruder, $category->id);
    }

    public function test_find_throws_model_not_found_for_a_missing_id(): void
    {
        $owner = User::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        $this->service->find($owner, 999999);
    }

    public function test_find_accepts_an_already_resolved_model(): void
    {
        $owner = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $owner->id]);

        $found = $this->service->find($owner, $category);

        $this->assertTrue($found->is($category));
    }

    public function test_create_assigns_the_actor_as_owner(): void
    {
        $owner = User::factory()->create();

        $category = $this->service->create($owner, ['name' => 'Woodworking']);

        $this->assertSame($owner->id, $category->user_id);
        $this->assertSame('Woodworking', $category->name);
    }

    public function test_create_ignores_a_client_supplied_user_id_in_favour_of_the_actor(): void
    {
        $owner = User::factory()->create();
        $victim = User::factory()->create();

        $category = $this->service->create($owner, [
            'name' => 'Woodworking',
            'user_id' => $victim->id,
        ]);

        $this->assertSame($owner->id, $category->user_id);
    }

    public function test_create_throws_nothing_for_any_authenticated_actor(): void
    {
        $owner = User::factory()->create();

        $category = $this->service->create($owner, ['name' => 'Woodworking']);

        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }

    public function test_update_persists_changes_for_the_owner(): void
    {
        $owner = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $owner->id, 'name' => 'Fitness']);

        $updated = $this->service->update($owner, $category, ['name' => 'Health']);

        $this->assertSame('Health', $updated->name);
        $this->assertDatabaseHas('categories', ['id' => $category->id, 'name' => 'Health']);
    }

    public function test_update_throws_for_a_non_owner(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $owner->id, 'name' => 'Fitness']);

        try {
            $this->service->update($intruder, $category, ['name' => 'Hijacked']);
            $this->fail('Expected an AuthorizationException.');
        } catch (AuthorizationException) {
            //
        }

        $this->assertDatabaseHas('categories', ['id' => $category->id, 'name' => 'Fitness']);
    }

    public function test_delete_removes_the_category_for_the_owner(): void
    {
        $owner = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $owner->id]);

        $this->service->delete($owner, $category);

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_delete_keeps_the_goals_and_leaves_them_uncategorised(): void
    {
        $owner = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $owner->id]);
        $goal = Goal::factory()->create(['user_id' => $owner->id, 'category_id' => $category->id]);

        $this->service->delete($owner, $category);

        $this->assertDatabaseHas('goals', ['id' => $goal->id, 'category_id' => null]);
    }

    public function test_delete_throws_for_a_non_owner(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $owner->id]);

        try {
            $this->service->delete($intruder, $category);
            $this->fail('Expected an AuthorizationException.');
        } catch (AuthorizationException) {
            //
        }

        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }
}
