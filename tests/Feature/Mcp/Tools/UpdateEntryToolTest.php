<?php

namespace Tests\Feature\Mcp\Tools;

use App\Mcp\Servers\IgniteServer;
use App\Mcp\Tools\UpdateEntryTool;
use App\Models\Goal;
use App\Models\GoalEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UpdateEntryToolTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_write_token_updates_an_entry_and_preserves_the_goal_value(): void
    {
        $user = User::factory()->create();
        $goal = Goal::factory()->create([
            'user_id' => $user->id,
            'type' => 'quantifiable',
            'current_value' => 100,
            'target_value' => 1000,
        ]);
        $entry = GoalEntry::factory()->create([
            'goal_id' => $goal->id,
            'previous_value' => 50,
            'value' => 60,
        ]);

        Sanctum::actingAs($user, ['read', 'write']);

        IgniteServer::tool(UpdateEntryTool::class, [
            'entry_id' => $entry->id,
            'increment' => 25,
        ])->assertOk();

        $this->assertSame(75.0, (float) $entry->fresh()->value);
        $this->assertSame(115.0, (float) $goal->fresh()->current_value);
    }

    public function test_a_token_without_the_write_ability_cannot_use_the_tool(): void
    {
        $user = User::factory()->create();
        $goal = Goal::factory()->create(['user_id' => $user->id]);
        $entry = GoalEntry::factory()->create(['goal_id' => $goal->id]);

        Sanctum::actingAs($user, ['read']);

        IgniteServer::tool(UpdateEntryTool::class, [
            'entry_id' => $entry->id,
            'increment' => 25,
        ])->assertHasErrors();
    }

    public function test_it_denies_updating_another_users_entry(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $goal = Goal::factory()->create(['user_id' => $owner->id]);
        $entry = GoalEntry::factory()->create(['goal_id' => $goal->id]);

        Sanctum::actingAs($intruder, ['read', 'write']);

        IgniteServer::tool(UpdateEntryTool::class, [
            'entry_id' => $entry->id,
            'increment' => 25,
        ])->assertHasErrors();
    }
}
