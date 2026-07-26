<?php

namespace Tests\Feature\Mcp\Tools;

use App\Mcp\Servers\IgniteServer;
use App\Mcp\Tools\UpdateGoalTool;
use App\Models\Goal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UpdateGoalToolTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_partial_update_changes_only_the_provided_field(): void
    {
        $user = User::factory()->create();
        $goal = Goal::factory()->create([
            'user_id' => $user->id,
            'title' => 'Old title',
            'points' => 250,
            'is_public' => true,
            'type' => 'simple',
        ]);

        Sanctum::actingAs($user, ['read', 'write']);

        IgniteServer::tool(UpdateGoalTool::class, [
            'goal_id' => $goal->id,
            'title' => 'New title',
        ])->assertOk();

        $fresh = $goal->fresh();

        $this->assertSame('New title', $fresh->title);
        // Omitted operational fields must keep their existing values rather
        // than being wiped or guessed. This is the contract that lets an AI
        // update a goal it read via `get_goal` (which omits points/is_public).
        $this->assertSame(250, $fresh->points);
        $this->assertTrue((bool) $fresh->is_public);
    }

    public function test_a_deadline_after_the_existing_start_date_is_accepted_with_only_deadline_sent(): void
    {
        $user = User::factory()->create();
        $goal = Goal::factory()->create([
            'user_id' => $user->id,
            'start_date' => '2026-01-01',
            'deadline' => null,
        ]);

        Sanctum::actingAs($user, ['read', 'write']);

        IgniteServer::tool(UpdateGoalTool::class, [
            'goal_id' => $goal->id,
            'deadline' => '2026-06-01',
        ])->assertOk();

        $this->assertSame('2026-06-01', $goal->fresh()->deadline->format('Y-m-d'));
    }

    public function test_a_deadline_before_the_existing_start_date_is_rejected_even_when_start_date_is_omitted(): void
    {
        $user = User::factory()->create();
        $goal = Goal::factory()->create([
            'user_id' => $user->id,
            'start_date' => '2026-06-01',
            'deadline' => null,
        ]);

        Sanctum::actingAs($user, ['read', 'write']);

        IgniteServer::tool(UpdateGoalTool::class, [
            'goal_id' => $goal->id,
            'deadline' => '2026-01-01',
        ])->assertHasErrors();

        // The rejected value must not have been written.
        $this->assertNull($goal->fresh()->deadline);
    }

    public function test_an_empty_payload_is_rejected(): void
    {
        $user = User::factory()->create();
        $goal = Goal::factory()->create(['user_id' => $user->id, 'title' => 'Untouched']);

        Sanctum::actingAs($user, ['read', 'write']);

        IgniteServer::tool(UpdateGoalTool::class, [
            'goal_id' => $goal->id,
        ])->assertHasErrors();

        $this->assertSame('Untouched', $goal->fresh()->title);
    }

    public function test_a_token_without_the_write_ability_cannot_update_a_goal(): void
    {
        $user = User::factory()->create();
        $goal = Goal::factory()->create(['user_id' => $user->id, 'title' => 'Old']);

        Sanctum::actingAs($user, ['read']);

        IgniteServer::tool(UpdateGoalTool::class, [
            'goal_id' => $goal->id,
            'title' => 'New',
        ])->assertHasErrors();

        $this->assertSame('Old', $goal->fresh()->title);
    }

    public function test_it_denies_updating_another_users_goal(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $goal = Goal::factory()->create(['user_id' => $owner->id, 'title' => 'Old']);

        Sanctum::actingAs($intruder, ['read', 'write']);

        IgniteServer::tool(UpdateGoalTool::class, [
            'goal_id' => $goal->id,
            'title' => 'Hijacked',
        ])->assertHasErrors();

        $this->assertSame('Old', $goal->fresh()->title);
    }
}
