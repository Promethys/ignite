<?php

namespace Tests\Feature\Mcp\Tools;

use App\Mcp\Servers\IgniteServer;
use App\Mcp\Tools\CompleteGoalTool;
use App\Models\Goal;
use App\Models\Milestone;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CompleteGoalToolTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_write_token_completes_the_owners_goal(): void
    {
        $user = User::factory()->create();
        $goal = Goal::factory()->inProgress()->create(['user_id' => $user->id]);

        Sanctum::actingAs($user, ['read', 'write']);

        IgniteServer::tool(CompleteGoalTool::class, ['goal_id' => $goal->id])
            ->assertOk();

        $fresh = $goal->fresh();

        $this->assertSame('completed', $fresh->status);
        $this->assertNotNull($fresh->completed_at);
    }

    public function test_a_token_without_the_write_ability_cannot_complete_a_goal(): void
    {
        $user = User::factory()->create();
        $goal = Goal::factory()->inProgress()->create(['user_id' => $user->id]);

        Sanctum::actingAs($user, ['read']);

        IgniteServer::tool(CompleteGoalTool::class, ['goal_id' => $goal->id])
            ->assertHasErrors();
    }

    public function test_it_denies_completing_another_users_goal(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $goal = Goal::factory()->inProgress()->create(['user_id' => $owner->id]);

        Sanctum::actingAs($intruder, ['read', 'write']);

        IgniteServer::tool(CompleteGoalTool::class, ['goal_id' => $goal->id])
            ->assertHasErrors();

        $this->assertNotSame('completed', $goal->fresh()->status);
    }

    public function test_the_milestone_summary_is_accurate_after_completing(): void
    {
        $user = User::factory()->create();
        $goal = Goal::factory()->inProgress()->create(['user_id' => $user->id, 'type' => 'multi_step']);
        Milestone::factory()->count(3)->create(['goal_id' => $goal->id, 'completed_at' => null]);
        Milestone::factory()->count(2)->create(['goal_id' => $goal->id, 'completed_at' => now()]);

        Sanctum::actingAs($user, ['read', 'write']);

        // Without `fresh('milestones')` this reports {total: 0, completed: 0}.
        IgniteServer::tool(CompleteGoalTool::class, ['goal_id' => $goal->id])
            ->assertOk()
            ->assertStructuredContent(fn (AssertableJson $json) => $json
                ->where('milestone_summary', ['total' => 5, 'completed' => 2])
                ->etc());
    }
}
