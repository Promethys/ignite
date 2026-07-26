<?php

namespace Tests\Feature\Mcp\Tools;

use App\Mcp\Servers\IgniteServer;
use App\Mcp\Tools\AddMilestoneTool;
use App\Models\Goal;
use App\Models\Milestone;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AddMilestoneToolTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_write_token_adds_a_milestone_to_the_goal(): void
    {
        $user = User::factory()->create();
        $goal = Goal::factory()->create(['user_id' => $user->id, 'type' => 'multi_step']);

        Sanctum::actingAs($user, ['read', 'write']);

        IgniteServer::tool(AddMilestoneTool::class, [
            'goal_id' => $goal->id,
            'title' => 'Draft the outline',
        ])
            ->assertOk()
            ->assertStructuredContent(fn (AssertableJson $json) => $json
                ->where('title', 'Draft the outline')
                ->where('order', 1)
                ->etc());

        $this->assertDatabaseHas('milestones', [
            'goal_id' => $goal->id,
            'title' => 'Draft the outline',
        ]);
    }

    public function test_the_new_milestone_is_appended_after_existing_ones(): void
    {
        $user = User::factory()->create();
        $goal = Goal::factory()->create(['user_id' => $user->id, 'type' => 'multi_step']);
        Milestone::factory()->create(['goal_id' => $goal->id, 'order' => 2]);

        Sanctum::actingAs($user, ['read', 'write']);

        IgniteServer::tool(AddMilestoneTool::class, [
            'goal_id' => $goal->id,
            'title' => 'Second step',
        ])
            ->assertOk()
            ->assertStructuredContent(fn (AssertableJson $json) => $json
                ->where('order', 3)
                ->etc());
    }

    public function test_a_title_is_required(): void
    {
        $user = User::factory()->create();
        $goal = Goal::factory()->create(['user_id' => $user->id]);

        Sanctum::actingAs($user, ['read', 'write']);

        IgniteServer::tool(AddMilestoneTool::class, [
            'goal_id' => $goal->id,
        ])->assertHasErrors();
    }

    public function test_a_token_without_the_write_ability_cannot_use_the_tool(): void
    {
        $user = User::factory()->create();
        $goal = Goal::factory()->create(['user_id' => $user->id]);

        Sanctum::actingAs($user, ['read']);

        IgniteServer::tool(AddMilestoneTool::class, [
            'goal_id' => $goal->id,
            'title' => 'Blocked',
        ])->assertHasErrors();
    }

    public function test_it_denies_adding_a_milestone_to_another_users_goal(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $goal = Goal::factory()->create(['user_id' => $owner->id]);

        Sanctum::actingAs($intruder, ['read', 'write']);

        IgniteServer::tool(AddMilestoneTool::class, [
            'goal_id' => $goal->id,
            'title' => 'Sneaky',
        ])->assertHasErrors();

        $this->assertDatabaseMissing('milestones', ['title' => 'Sneaky']);
    }
}
