<?php

namespace Tests\Feature\Mcp\Tools;

use App\Mcp\Servers\IgniteServer;
use App\Mcp\Tools\CompleteMilestoneTool;
use App\Models\Goal;
use App\Models\Milestone;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CompleteMilestoneToolTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_write_token_completes_the_milestone(): void
    {
        $user = User::factory()->create();
        $goal = Goal::factory()->create(['user_id' => $user->id, 'type' => 'multi_step']);
        $milestone = Milestone::factory()->create(['goal_id' => $goal->id, 'completed_at' => null]);

        Sanctum::actingAs($user, ['read', 'write']);

        IgniteServer::tool(CompleteMilestoneTool::class, [
            'milestone_id' => $milestone->id,
        ])
            ->assertOk()
            ->assertStructuredContent(fn (AssertableJson $json) => $json
                ->where('is_completed', true)
                ->etc());

        $this->assertNotNull($milestone->fresh()->completed_at);
    }

    public function test_a_nonexistent_milestone_is_rejected(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['read', 'write']);

        IgniteServer::tool(CompleteMilestoneTool::class, [
            'milestone_id' => 999999,
        ])->assertHasErrors();
    }

    public function test_a_token_without_the_write_ability_cannot_use_the_tool(): void
    {
        $user = User::factory()->create();
        $goal = Goal::factory()->create(['user_id' => $user->id]);
        $milestone = Milestone::factory()->create(['goal_id' => $goal->id]);

        Sanctum::actingAs($user, ['read']);

        IgniteServer::tool(CompleteMilestoneTool::class, [
            'milestone_id' => $milestone->id,
        ])->assertHasErrors();
    }

    public function test_it_denies_completing_another_users_milestone(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $goal = Goal::factory()->create(['user_id' => $owner->id]);
        $milestone = Milestone::factory()->create(['goal_id' => $goal->id, 'completed_at' => null]);

        Sanctum::actingAs($intruder, ['read', 'write']);

        IgniteServer::tool(CompleteMilestoneTool::class, [
            'milestone_id' => $milestone->id,
        ])->assertHasErrors();

        $this->assertNull($milestone->fresh()->completed_at);
    }
}
