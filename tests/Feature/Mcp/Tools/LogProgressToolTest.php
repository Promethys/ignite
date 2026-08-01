<?php

namespace Tests\Feature\Mcp\Tools;

use App\Mcp\Servers\IgniteServer;
use App\Mcp\Tools\LogProgressTool;
use App\Models\Goal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LogProgressToolTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_write_token_logs_progress_and_shifts_the_goal_value(): void
    {
        $user = User::factory()->create();
        $goal = Goal::factory()->create([
            'user_id' => $user->id,
            'type' => 'quantifiable',
            'current_value' => 20,
            'target_value' => 100,
        ]);

        Sanctum::actingAs($user, ['read', 'write']);

        IgniteServer::tool(LogProgressTool::class, [
            'goal_id' => $goal->id,
            'increment' => 5,
        ])->assertOk();

        $this->assertSame(25.0, (float) $goal->fresh()->current_value);
    }

    public function test_a_token_without_the_write_ability_cannot_use_the_tool(): void
    {
        $user = User::factory()->create();
        $goal = Goal::factory()->create(['user_id' => $user->id, 'type' => 'quantifiable']);

        Sanctum::actingAs($user, ['read']);

        IgniteServer::tool(LogProgressTool::class, [
            'goal_id' => $goal->id,
            'increment' => 5,
        ])->assertHasErrors();
    }

    public function test_it_denies_progress_on_another_users_goal(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $goal = Goal::factory()->create(['user_id' => $owner->id, 'type' => 'quantifiable']);

        Sanctum::actingAs($intruder, ['read', 'write']);

        IgniteServer::tool(LogProgressTool::class, [
            'goal_id' => $goal->id,
            'increment' => 5,
        ])->assertHasErrors();
    }

    public function test_the_note_limit_matches_the_web_form(): void
    {
        $user = User::factory()->create();
        $goal = Goal::factory()->create(['user_id' => $user->id, 'type' => 'quantifiable']);

        Sanctum::actingAs($user, ['read', 'write']);

        IgniteServer::tool(LogProgressTool::class, [
            'goal_id' => $goal->id,
            'increment' => 5,
            'note' => str_repeat('a', 2000),
        ])->assertOk();

        IgniteServer::tool(LogProgressTool::class, [
            'goal_id' => $goal->id,
            'increment' => 5,
            'note' => str_repeat('a', 2001),
        ])->assertHasErrors();
    }

    public function test_it_rejects_logging_progress_on_a_recurring_goal(): void
    {
        $user = User::factory()->create();
        $goal = Goal::factory()->create([
            'user_id' => $user->id,
            'type' => 'recurring',
            'recurrence' => 'daily',
        ]);

        Sanctum::actingAs($user, ['read', 'write']);

        IgniteServer::tool(LogProgressTool::class, [
            'goal_id' => $goal->id,
            'increment' => 5,
        ])->assertHasErrors();
    }
}
