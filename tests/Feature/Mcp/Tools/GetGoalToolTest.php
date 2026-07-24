<?php

namespace Tests\Feature\Mcp\Tools;

use App\Mcp\Servers\IgniteServer;
use App\Mcp\Tools\GetGoalTool;
use App\Models\Goal;
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
}
