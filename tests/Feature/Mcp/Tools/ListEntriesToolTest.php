<?php

namespace Tests\Feature\Mcp\Tools;

use App\Mcp\Servers\IgniteServer;
use App\Mcp\Tools\ListEntriesTool;
use App\Models\Goal;
use App\Models\GoalEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ListEntriesToolTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_read_token_lists_the_goals_entries(): void
    {
        $user = User::factory()->create();
        $goal = Goal::factory()->create(['user_id' => $user->id]);
        GoalEntry::factory()->count(2)->create(['goal_id' => $goal->id]);

        Sanctum::actingAs($user, ['read']);

        IgniteServer::tool(ListEntriesTool::class, ['goal_id' => $goal->id])
            ->assertOk();
    }

    public function test_a_token_without_the_read_ability_cannot_use_the_tool(): void
    {
        $user = User::factory()->create();
        $goal = Goal::factory()->create(['user_id' => $user->id]);

        Sanctum::actingAs($user, ['delete']);

        IgniteServer::tool(ListEntriesTool::class, ['goal_id' => $goal->id])
            ->assertHasErrors();
    }

    public function test_it_denies_listing_another_users_goal_entries(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $goal = Goal::factory()->create(['user_id' => $owner->id]);
        GoalEntry::factory()->create(['goal_id' => $goal->id]);

        Sanctum::actingAs($intruder, ['read']);

        IgniteServer::tool(ListEntriesTool::class, ['goal_id' => $goal->id])
            ->assertHasErrors();
    }
}
