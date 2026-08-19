<?php

namespace Tests\Feature\Mcp\Tools;

use App\Mcp\Servers\IgniteServer;
use App\Mcp\Tools\ListCategoriesTool;
use App\Models\Category;
use App\Models\Goal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ListCategoriesToolTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_read_scoped_token_lists_the_actors_categories(): void
    {
        $user = User::factory()->create();
        Category::factory()->count(2)->create(['user_id' => $user->id]);

        Sanctum::actingAs($user, ['read']);

        IgniteServer::tool(ListCategoriesTool::class)
            ->assertOk()
            ->assertSee('Retrieved 2 categories.');
    }

    public function test_it_lists_only_the_actors_own_categories(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        Category::factory()->count(2)->create(['user_id' => $owner->id]);
        Category::factory()->create(['user_id' => $other->id]);

        Sanctum::actingAs($owner, ['read']);

        IgniteServer::tool(ListCategoriesTool::class)
            ->assertOk()
            ->assertSee('Retrieved 2 categories.');
    }

    public function test_a_token_without_the_read_ability_cannot_use_the_tool(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['delete']);

        IgniteServer::tool(ListCategoriesTool::class)
            ->assertHasErrors();
    }

    public function test_structured_content_is_wrapped_in_a_categories_object(): void
    {
        $user = User::factory()->create();
        Category::factory()->create(['user_id' => $user->id]);

        Sanctum::actingAs($user, ['read']);

        IgniteServer::tool(ListCategoriesTool::class)
            ->assertOk()
            ->assertStructuredContent(fn (AssertableJson $json) => $json
                ->has('categories')
                ->where('total', 1)
                ->etc());
    }

    public function test_each_category_reports_how_many_goals_are_filed_under_it(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id]);
        Goal::factory()->count(3)->create(['user_id' => $user->id, 'category_id' => $category->id]);
        Goal::factory()->create(['user_id' => $user->id, 'category_id' => null]);

        Sanctum::actingAs($user, ['read']);

        IgniteServer::tool(ListCategoriesTool::class)
            ->assertOk()
            ->assertStructuredContent(fn (AssertableJson $json) => $json
                ->where('categories.0.goals_count', 3)
                ->etc());
    }

    public function test_the_goal_count_is_broken_down_by_status(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id]);

        Goal::factory()->count(2)->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'in_progress',
        ]);
        Goal::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'completed',
        ]);

        Sanctum::actingAs($user, ['read']);

        IgniteServer::tool(ListCategoriesTool::class)
            ->assertOk()
            ->assertStructuredContent(fn (AssertableJson $json) => $json
                ->where('categories.0.goals_count', 3)
                ->where('categories.0.active_goals_count', 2)
                ->where('categories.0.completed_goals_count', 1)
                ->etc());
    }

    public function test_the_slug_is_never_exposed(): void
    {
        $user = User::factory()->create();
        Category::factory()->create(['user_id' => $user->id]);

        Sanctum::actingAs($user, ['read']);

        IgniteServer::tool(ListCategoriesTool::class)
            ->assertOk()
            ->assertStructuredContent(fn (AssertableJson $json) => $json
                ->missing('categories.0.slug')
                ->etc());
    }

    public function test_categories_come_back_in_display_order(): void
    {
        $user = User::factory()->create();
        Category::factory()->create(['user_id' => $user->id, 'name' => 'Third', 'order' => 3]);
        Category::factory()->create(['user_id' => $user->id, 'name' => 'First', 'order' => 1]);
        Category::factory()->create(['user_id' => $user->id, 'name' => 'Second', 'order' => 2]);

        Sanctum::actingAs($user, ['read']);

        IgniteServer::tool(ListCategoriesTool::class)
            ->assertOk()
            ->assertStructuredContent(fn (AssertableJson $json) => $json
                ->where('categories.0.name', 'First')
                ->where('categories.1.name', 'Second')
                ->where('categories.2.name', 'Third')
                ->etc());
    }

    public function test_a_local_session_without_a_token_may_use_the_tool(): void
    {
        $user = User::factory()->create();
        Category::factory()->create(['user_id' => $user->id]);

        IgniteServer::actingAs($user)
            ->tool(ListCategoriesTool::class)
            ->assertOk();
    }
}
