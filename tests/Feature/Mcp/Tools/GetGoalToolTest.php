<?php

namespace Tests\Feature\Mcp\Tools;

use App\Mcp\Servers\IgniteServer;
use App\Mcp\Tools\GetGoalTool;
use App\Models\Goal;
use App\Models\GoalEntry;
use App\Models\Milestone;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class GetGoalToolTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_read_scoped_owner_gets_the_requested_goal(): void
    {
        $owner = User::factory()->create();
        $goal = Goal::factory()->create(['user_id' => $owner->id]);

        Sanctum::actingAs($owner, ['read']);

        IgniteServer::tool(GetGoalTool::class, ['goal_id' => $goal->id])
            ->assertOk()
            ->assertStructuredContent(fn (AssertableJson $json) => $json->where('id', $goal->id)->etc());
    }

    public function test_a_goal_id_is_required(): void
    {
        $owner = User::factory()->create();

        Sanctum::actingAs($owner, ['read']);

        IgniteServer::tool(GetGoalTool::class)
            ->assertHasErrors();
    }

    public function test_it_denies_access_to_another_users_goal(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $goal = Goal::factory()->create(['user_id' => $owner->id]);

        Sanctum::actingAs($intruder, ['read']);

        IgniteServer::tool(GetGoalTool::class, ['goal_id' => $goal->id])
            ->assertHasErrors();
    }

    public function test_a_token_without_the_read_ability_cannot_use_the_tool(): void
    {
        $owner = User::factory()->create();
        $goal = Goal::factory()->create(['user_id' => $owner->id]);

        Sanctum::actingAs($owner, ['delete']);

        IgniteServer::tool(GetGoalTool::class, ['goal_id' => $goal->id])
            ->assertHasErrors();
    }

    public function test_a_local_session_without_a_token_may_use_the_tool(): void
    {
        $owner = User::factory()->create();
        $goal = Goal::factory()->create(['user_id' => $owner->id]);

        IgniteServer::actingAs($owner)
            ->tool(GetGoalTool::class, ['goal_id' => $goal->id])
            ->assertOk();
    }

    public function test_get_goal_includes_entries_and_milestones_and_never_the_user(): void
    {
        $owner = User::factory()->create();
        $goal = Goal::factory()->create(['user_id' => $owner->id]);
        GoalEntry::factory()->count(2)->create(['goal_id' => $goal->id]);
        Milestone::factory()->create(['goal_id' => $goal->id]);

        Sanctum::actingAs($owner, ['read']);

        IgniteServer::tool(GetGoalTool::class, ['goal_id' => $goal->id])
            ->assertOk()
            ->assertStructuredContent(fn (AssertableJson $json) => $json
                ->has('entries', 2)
                ->has('milestones', 1)
                ->missing('user')
                ->missing('user_id')
                ->etc());
    }

    public function test_get_goal_rejects_a_nonexistent_id(): void
    {
        $owner = User::factory()->create();

        Sanctum::actingAs($owner, ['read']);

        IgniteServer::tool(GetGoalTool::class, ['goal_id' => 999999])
            ->assertHasErrors();
    }

    public function test_get_goal_returns_the_clean_streak_shape_for_a_recurring_goal(): void
    {
        $owner = User::factory()->create();
        $goal = Goal::factory()->create([
            'user_id' => $owner->id,
            'type' => 'recurring',
            'recurrence' => 'daily',
        ]);

        Sanctum::actingAs($owner, ['read']);

        IgniteServer::tool(GetGoalTool::class, ['goal_id' => $goal->id])
            ->assertOk()
            ->assertStructuredContent(fn (AssertableJson $json) => $json
                ->has('streak', fn (AssertableJson $streak) => $streak
                    ->hasAll(['current', 'longest', 'unit', 'current_period_satisfied'])
                    ->missing('anchorDate'))
                ->etc());
    }
}
