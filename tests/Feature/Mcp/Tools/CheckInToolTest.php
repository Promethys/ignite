<?php

namespace Tests\Feature\Mcp\Tools;

use App\Mcp\Servers\IgniteServer;
use App\Mcp\Tools\CheckInTool;
use App\Models\Goal;
use App\Models\GoalEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CheckInToolTest extends TestCase
{
    use RefreshDatabase;

    private function recurringGoal(User $user): Goal
    {
        return Goal::factory()->create([
            'user_id' => $user->id,
            'type' => 'recurring',
            'recurrence' => 'daily',
            'current_value' => 3,
            'start_date' => null,
        ]);
    }

    public function test_a_write_token_records_a_check_in_without_touching_current_value(): void
    {
        $user = User::factory()->create();
        $goal = $this->recurringGoal($user);

        Sanctum::actingAs($user, ['read', 'write']);

        IgniteServer::tool(CheckInTool::class, [
            'goal_id' => $goal->id,
            'entry_date' => '2026-07-20',
        ])->assertOk();

        $this->assertSame(3.0, (float) $goal->fresh()->current_value);
        $this->assertDatabaseHas('goal_entries', [
            'goal_id' => $goal->id,
            'value' => 1,
        ]);
    }

    public function test_a_token_without_the_write_ability_cannot_use_the_tool(): void
    {
        $user = User::factory()->create();
        $goal = $this->recurringGoal($user);

        Sanctum::actingAs($user, ['read']);

        IgniteServer::tool(CheckInTool::class, [
            'goal_id' => $goal->id,
            'entry_date' => '2026-07-20',
        ])->assertHasErrors();
    }

    public function test_it_denies_a_check_in_on_another_users_goal(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $goal = $this->recurringGoal($owner);

        Sanctum::actingAs($intruder, ['read', 'write']);

        IgniteServer::tool(CheckInTool::class, [
            'goal_id' => $goal->id,
            'entry_date' => '2026-07-20',
        ])->assertHasErrors();
    }

    public function test_it_rejects_a_check_in_on_a_non_recurring_goal(): void
    {
        $user = User::factory()->create();
        $goal = Goal::factory()->create([
            'user_id' => $user->id,
            'type' => 'quantifiable',
            'start_date' => null,
        ]);

        Sanctum::actingAs($user, ['read', 'write']);

        IgniteServer::tool(CheckInTool::class, [
            'goal_id' => $goal->id,
            'entry_date' => '2026-07-20',
        ])->assertHasErrors();
    }

    public function test_it_rejects_a_second_check_in_in_the_same_period(): void
    {
        $user = User::factory()->create();
        $goal = $this->recurringGoal($user);
        GoalEntry::factory()->create(['goal_id' => $goal->id, 'entry_date' => '2026-07-20']);

        Sanctum::actingAs($user, ['read', 'write']);

        IgniteServer::tool(CheckInTool::class, [
            'goal_id' => $goal->id,
            'entry_date' => '2026-07-20',
        ])->assertHasErrors();
    }
}
