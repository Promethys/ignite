<?php

namespace Tests\Feature\Mcp\Tools;

use App\Mcp\Servers\IgniteServer;
use App\Mcp\Tools\ListGoalsTool;
use App\Models\Category;
use App\Models\Goal;
use App\Models\Milestone;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ListGoalsToolTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_read_scoped_token_lists_the_actors_goals(): void
    {
        $user = User::factory()->create();
        Goal::factory()->count(2)->create(['user_id' => $user->id]);

        Sanctum::actingAs($user, ['read']);

        IgniteServer::tool(ListGoalsTool::class)
            ->assertOk()
            ->assertSee('Retrieved 2 goals.');
    }

    public function test_it_lists_only_the_actors_own_goals(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        Goal::factory()->count(2)->create(['user_id' => $owner->id]);
        Goal::factory()->create(['user_id' => $other->id]);

        Sanctum::actingAs($owner, ['read']);

        IgniteServer::tool(ListGoalsTool::class)
            ->assertOk()
            ->assertSee('Retrieved 2 goals.');
    }

    public function test_a_token_without_the_read_ability_cannot_use_the_tool(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['delete']);

        IgniteServer::tool(ListGoalsTool::class)
            ->assertHasErrors();
    }

    public function test_structured_content_is_wrapped_in_a_goals_object(): void
    {
        $user = User::factory()->create();
        Goal::factory()->create(['user_id' => $user->id]);

        Sanctum::actingAs($user, ['read']);

        IgniteServer::tool(ListGoalsTool::class)
            ->assertOk()
            ->assertStructuredContent(fn (AssertableJson $json) => $json->has('goals')->etc());
    }

    public function test_a_local_session_without_a_token_may_use_the_tool(): void
    {
        $user = User::factory()->create();
        Goal::factory()->create(['user_id' => $user->id]);

        IgniteServer::actingAs($user)
            ->tool(ListGoalsTool::class)
            ->assertOk();
    }

    public function test_the_list_includes_a_milestone_summary_without_loading_milestones(): void
    {
        $user = User::factory()->create();
        $goal = Goal::factory()->create(['user_id' => $user->id, 'type' => 'multi_step']);
        Milestone::factory()->count(3)->create(['goal_id' => $goal->id, 'completed_at' => null]);
        Milestone::factory()->count(2)->create(['goal_id' => $goal->id, 'completed_at' => now()]);

        Sanctum::actingAs($user, ['read']);

        IgniteServer::tool(ListGoalsTool::class)
            ->assertOk()
            ->assertStructuredContent(fn (AssertableJson $json) => $json
                ->has('goals.0.milestone_summary')
                ->where('goals.0.milestone_summary', ['total' => 5, 'completed' => 2])
                ->etc());
    }

    public function test_the_status_filter_narrows_the_list(): void
    {
        $user = User::factory()->create();
        Goal::factory()->create(['user_id' => $user->id, 'status' => 'in_progress']);
        Goal::factory()->create(['user_id' => $user->id, 'status' => 'completed']);

        Sanctum::actingAs($user, ['read']);

        IgniteServer::tool(ListGoalsTool::class, ['status' => 'completed'])
            ->assertOk()
            ->assertSee('Retrieved 1 goals.');
    }

    public function test_the_search_filter_matches_titles(): void
    {
        $user = User::factory()->create();
        Goal::factory()->create(['user_id' => $user->id, 'title' => 'Learn the cello']);
        Goal::factory()->create(['user_id' => $user->id, 'title' => 'Run a marathon']);

        Sanctum::actingAs($user, ['read']);

        IgniteServer::tool(ListGoalsTool::class, ['search' => 'cello'])
            ->assertOk()
            ->assertSee('Retrieved 1 goals.');
    }

    public function test_the_limit_caps_the_number_of_goals_returned(): void
    {
        $user = User::factory()->create();
        Goal::factory()->count(3)->create(['user_id' => $user->id]);

        Sanctum::actingAs($user, ['read']);

        IgniteServer::tool(ListGoalsTool::class, ['limit' => 2])
            ->assertOk()
            ->assertSee('Retrieved 2 of 3 goals.')
            ->assertStructuredContent(fn (AssertableJson $json) => $json
                ->has('goals', 2)
                ->where('total', 3)
                ->where('limit', 2)
                ->etc());
    }

    public function test_an_unlimited_list_reports_no_limit(): void
    {
        $user = User::factory()->create();
        Goal::factory()->count(3)->create(['user_id' => $user->id]);

        Sanctum::actingAs($user, ['read']);

        IgniteServer::tool(ListGoalsTool::class)
            ->assertOk()
            ->assertSee('Retrieved 3 goals.')
            ->assertStructuredContent(fn (AssertableJson $json) => $json
                ->where('total', 3)
                ->where('limit', null)
                ->etc());
    }

    public function test_a_goal_without_a_category_is_listed_without_error(): void
    {
        $user = User::factory()->create();
        Goal::factory()->create(['user_id' => $user->id, 'category_id' => null]);

        Sanctum::actingAs($user, ['read']);

        IgniteServer::tool(ListGoalsTool::class)
            ->assertOk()
            ->assertStructuredContent(fn (AssertableJson $json) => $json
                ->where('goals.0.category', null)
                ->etc());
    }

    public function test_a_categorised_goal_exposes_its_category(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id, 'name' => 'Health']);
        Goal::factory()->create(['user_id' => $user->id, 'category_id' => $category->id]);

        Sanctum::actingAs($user, ['read']);

        IgniteServer::tool(ListGoalsTool::class)
            ->assertOk()
            ->assertStructuredContent(fn (AssertableJson $json) => $json
                ->where('goals.0.category', ['id' => $category->id, 'name' => 'Health'])
                ->etc());
    }
}
