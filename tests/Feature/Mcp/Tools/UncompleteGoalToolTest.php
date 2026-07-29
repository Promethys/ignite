<?php

namespace Tests\Feature\Mcp\Tools;

use App\Mcp\Servers\IgniteServer;
use App\Mcp\Tools\UncompleteGoalTool;
use App\Models\Goal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UncompleteGoalToolTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_write_token_reverts_a_completed_goal(): void
    {
        $user = User::factory()->create();
        $goal = Goal::factory()->completed()->create(['user_id' => $user->id]);

        Sanctum::actingAs($user, ['read', 'write']);

        IgniteServer::tool(UncompleteGoalTool::class, [
            'goal_id' => $goal->id,
            'status' => 'in_progress',
        ])->assertOk();

        $fresh = $goal->fresh();

        $this->assertSame('in_progress', $fresh->status);
        $this->assertNull($fresh->completed_at);
    }

    public function test_uncomplete_rejects_the_completed_status(): void
    {
        $user = User::factory()->create();
        $goal = Goal::factory()->completed()->create(['user_id' => $user->id]);

        Sanctum::actingAs($user, ['read', 'write']);

        IgniteServer::tool(UncompleteGoalTool::class, [
            'goal_id' => $goal->id,
            'status' => 'completed',
        ])->assertHasErrors();
    }

    public function test_a_token_without_the_write_ability_cannot_revert_a_goal(): void
    {
        $user = User::factory()->create();
        $goal = Goal::factory()->completed()->create(['user_id' => $user->id]);

        Sanctum::actingAs($user, ['read']);

        IgniteServer::tool(UncompleteGoalTool::class, [
            'goal_id' => $goal->id,
            'status' => 'in_progress',
        ])->assertHasErrors();
    }

    public function test_it_denies_reverting_another_users_goal(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $goal = Goal::factory()->completed()->create(['user_id' => $owner->id]);

        Sanctum::actingAs($intruder, ['read', 'write']);

        IgniteServer::tool(UncompleteGoalTool::class, [
            'goal_id' => $goal->id,
            'status' => 'in_progress',
        ])->assertHasErrors();

        $this->assertSame('completed', $goal->fresh()->status);
    }
}
