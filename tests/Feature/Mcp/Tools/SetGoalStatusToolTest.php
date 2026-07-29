<?php

namespace Tests\Feature\Mcp\Tools;

use App\Mcp\Servers\IgniteServer;
use App\Mcp\Tools\SetGoalStatusTool;
use App\Models\Goal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SetGoalStatusToolTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_write_token_changes_the_goal_status(): void
    {
        $user = User::factory()->create();
        $goal = Goal::factory()->create([
            'user_id' => $user->id,
            'status' => 'not_started',
        ]);

        Sanctum::actingAs($user, ['read', 'write']);

        IgniteServer::tool(SetGoalStatusTool::class, [
            'goal_id' => $goal->id,
            'status' => 'in_progress',
        ])->assertOk();

        $this->assertSame('in_progress', $goal->fresh()->status);
    }

    public function test_a_token_without_the_write_ability_cannot_change_status(): void
    {
        $user = User::factory()->create();
        $goal = Goal::factory()->create(['user_id' => $user->id, 'status' => 'not_started']);

        Sanctum::actingAs($user, ['read']);

        IgniteServer::tool(SetGoalStatusTool::class, [
            'goal_id' => $goal->id,
            'status' => 'in_progress',
        ])->assertHasErrors();
    }

    public function test_it_denies_changing_another_users_goal_status(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $goal = Goal::factory()->create(['user_id' => $owner->id, 'status' => 'not_started']);

        Sanctum::actingAs($intruder, ['read', 'write']);

        IgniteServer::tool(SetGoalStatusTool::class, [
            'goal_id' => $goal->id,
            'status' => 'in_progress',
        ])->assertHasErrors();

        $this->assertSame('not_started', $goal->fresh()->status);
    }

    public function test_set_status_rejects_an_invalid_status(): void
    {
        $user = User::factory()->create();
        $goal = Goal::factory()->create(['user_id' => $user->id]);

        Sanctum::actingAs($user, ['read', 'write']);

        IgniteServer::tool(SetGoalStatusTool::class, [
            'goal_id' => $goal->id,
            'status' => 'frozen',
        ])->assertHasErrors();
    }
}
