<?php

namespace Tests\Feature\Mcp\Tools;

use App\Mcp\Servers\IgniteServer;
use App\Mcp\Tools\DeleteGoalTool;
use App\Models\Goal;
use App\Models\GoalEntry;
use App\Models\Milestone;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Laravel\Mcp\Server\Testing\TestResponse;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DeleteGoalToolTest extends TestCase
{
    use RefreshDatabase;

    private function confirmationTokenFrom(TestResponse $response): string
    {
        $token = null;

        $response->assertStructuredContent(function (AssertableJson $json) use (&$token) {
            $token = $json->toArray()['confirmation_token'] ?? null;
            $json->etc();
        });

        return (string) $token;
    }

    public function test_the_first_call_previews_the_cascade_and_deletes_nothing(): void
    {
        $user = User::factory()->create();
        $goal = Goal::factory()->create(['user_id' => $user->id, 'title' => 'Write a book']);
        Milestone::factory()->count(3)->create(['goal_id' => $goal->id]);
        GoalEntry::factory()->count(2)->create(['goal_id' => $goal->id]);

        Sanctum::actingAs($user, ['read', 'write', 'delete']);

        IgniteServer::tool(DeleteGoalTool::class, ['goal_id' => $goal->id])
            ->assertOk()
            ->assertSee("the goal 'Write a book'")
            ->assertSee('its 3 milestones and 2 progress entries');

        $this->assertDatabaseHas('goals', ['id' => $goal->id]);
    }

    public function test_a_confirmed_call_deletes_the_goal_and_its_children(): void
    {
        $user = User::factory()->create();
        $goal = Goal::factory()->create(['user_id' => $user->id]);
        $milestone = Milestone::factory()->create(['goal_id' => $goal->id]);
        $entry = GoalEntry::factory()->create(['goal_id' => $goal->id]);

        Sanctum::actingAs($user, ['read', 'write', 'delete']);

        $first = IgniteServer::tool(DeleteGoalTool::class, ['goal_id' => $goal->id]);
        $token = $this->confirmationTokenFrom($first);

        IgniteServer::tool(DeleteGoalTool::class, [
            'goal_id' => $goal->id,
            'confirmation_token' => $token,
        ])->assertOk();

        $this->assertDatabaseMissing('goals', ['id' => $goal->id]);
        $this->assertDatabaseMissing('milestones', ['id' => $milestone->id]);
        $this->assertDatabaseMissing('goal_entries', ['id' => $entry->id]);
    }

    public function test_a_confirmation_token_works_only_once(): void
    {
        $user = User::factory()->create();
        $goal = Goal::factory()->create(['user_id' => $user->id]);

        Sanctum::actingAs($user, ['read', 'write', 'delete']);

        $token = $this->confirmationTokenFrom(
            IgniteServer::tool(DeleteGoalTool::class, ['goal_id' => $goal->id])
        );

        IgniteServer::tool(DeleteGoalTool::class, [
            'goal_id' => $goal->id,
            'confirmation_token' => $token,
        ])->assertOk();

        $other = Goal::factory()->create(['user_id' => $user->id]);

        IgniteServer::tool(DeleteGoalTool::class, [
            'goal_id' => $other->id,
            'confirmation_token' => $token,
        ])->assertHasErrors();

        $this->assertDatabaseHas('goals', ['id' => $other->id]);
    }

    public function test_an_invalid_confirmation_token_does_not_delete(): void
    {
        $user = User::factory()->create();
        $goal = Goal::factory()->create(['user_id' => $user->id]);

        Sanctum::actingAs($user, ['read', 'write', 'delete']);

        IgniteServer::tool(DeleteGoalTool::class, [
            'goal_id' => $goal->id,
            'confirmation_token' => 'not-a-real-token',
        ])->assertHasErrors();

        $this->assertDatabaseHas('goals', ['id' => $goal->id]);
    }

    public function test_a_token_issued_for_one_goal_cannot_delete_another(): void
    {
        $user = User::factory()->create();
        $goal = Goal::factory()->create(['user_id' => $user->id]);
        $otherGoal = Goal::factory()->create(['user_id' => $user->id]);

        Sanctum::actingAs($user, ['read', 'write', 'delete']);

        $token = $this->confirmationTokenFrom(
            IgniteServer::tool(DeleteGoalTool::class, ['goal_id' => $goal->id])
        );

        IgniteServer::tool(DeleteGoalTool::class, [
            'goal_id' => $otherGoal->id,
            'confirmation_token' => $token,
        ])->assertHasErrors();

        $this->assertDatabaseHas('goals', ['id' => $otherGoal->id]);
    }

    public function test_a_token_without_the_delete_ability_cannot_use_the_tool(): void
    {
        $user = User::factory()->create();
        $goal = Goal::factory()->create(['user_id' => $user->id]);

        Sanctum::actingAs($user, ['read', 'write']);

        IgniteServer::tool(DeleteGoalTool::class, ['goal_id' => $goal->id])
            ->assertHasErrors();

        $this->assertDatabaseHas('goals', ['id' => $goal->id]);
    }

    public function test_it_denies_deleting_another_users_goal(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $goal = Goal::factory()->create(['user_id' => $owner->id]);

        Sanctum::actingAs($intruder, ['read', 'write', 'delete']);

        IgniteServer::tool(DeleteGoalTool::class, ['goal_id' => $goal->id])
            ->assertHasErrors();

        $this->assertDatabaseHas('goals', ['id' => $goal->id]);
    }
}
