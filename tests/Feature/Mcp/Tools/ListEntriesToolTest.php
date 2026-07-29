<?php

namespace Tests\Feature\Mcp\Tools;

use App\Mcp\Servers\IgniteServer;
use App\Mcp\Tools\ListEntriesTool;
use App\Models\Goal;
use App\Models\GoalEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
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

    public function test_structured_content_is_wrapped_in_an_entries_object(): void
    {
        $user = User::factory()->create();
        $goal = Goal::factory()->create(['user_id' => $user->id]);
        GoalEntry::factory()->count(2)->create(['goal_id' => $goal->id]);

        Sanctum::actingAs($user, ['read']);

        IgniteServer::tool(ListEntriesTool::class, ['goal_id' => $goal->id])
            ->assertOk()
            ->assertStructuredContent(fn (AssertableJson $json) => $json->has('entries')->etc());
    }

    public function test_the_total_count_exceeds_the_number_returned_when_the_cap_applies(): void
    {
        $user = User::factory()->create();
        $goal = Goal::factory()->create(['user_id' => $user->id]);
        GoalEntry::factory()->count(5)->create(['goal_id' => $goal->id]);

        Sanctum::actingAs($user, ['read']);

        IgniteServer::tool(ListEntriesTool::class, ['goal_id' => $goal->id, 'limit' => 2])
            ->assertOk()
            ->assertSee('Retrieved 2 of 5 progress entries.')
            ->assertStructuredContent(fn (AssertableJson $json) => $json
                ->where('total', 5)
                ->where('limit', 2)
                ->has('entries', 2)
                ->etc());
    }

    public function test_the_text_reports_the_full_count_when_nothing_is_truncated(): void
    {
        $user = User::factory()->create();
        $goal = Goal::factory()->create(['user_id' => $user->id]);
        GoalEntry::factory()->count(2)->create(['goal_id' => $goal->id]);

        Sanctum::actingAs($user, ['read']);

        IgniteServer::tool(ListEntriesTool::class, ['goal_id' => $goal->id])
            ->assertOk()
            ->assertSee('Retrieved 2 of 2 progress entries.');
    }

    public function test_the_from_filter_excludes_older_entries(): void
    {
        $user = User::factory()->create();
        $goal = Goal::factory()->create(['user_id' => $user->id]);
        GoalEntry::factory()->create(['goal_id' => $goal->id, 'entry_date' => '2026-01-01']);
        GoalEntry::factory()->create(['goal_id' => $goal->id, 'entry_date' => '2026-06-01']);

        Sanctum::actingAs($user, ['read']);

        IgniteServer::tool(ListEntriesTool::class, [
            'goal_id' => $goal->id,
            'from' => '2026-03-01',
        ])
            ->assertOk()
            ->assertSee('Retrieved 1 of 1 progress entries.');
    }

    public function test_the_search_filter_matches_notes(): void
    {
        $user = User::factory()->create();
        $goal = Goal::factory()->create(['user_id' => $user->id]);
        GoalEntry::factory()->create(['goal_id' => $goal->id, 'note' => 'Felt great']);
        GoalEntry::factory()->create(['goal_id' => $goal->id, 'note' => 'Tired today']);

        Sanctum::actingAs($user, ['read']);

        IgniteServer::tool(ListEntriesTool::class, [
            'goal_id' => $goal->id,
            'search' => 'great',
        ])
            ->assertOk()
            ->assertSee('Retrieved 1 of 1 progress entries.');
    }
}
