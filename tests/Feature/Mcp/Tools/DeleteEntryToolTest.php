<?php

namespace Tests\Feature\Mcp\Tools;

use App\Mcp\Servers\IgniteServer;
use App\Mcp\Tools\DeleteEntryTool;
use App\Models\Goal;
use App\Models\GoalEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Laravel\Mcp\Server\Testing\TestResponse;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DeleteEntryToolTest extends TestCase
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

    public function test_the_first_call_previews_the_value_change_and_deletes_nothing(): void
    {
        $user = User::factory()->create();
        $goal = Goal::factory()->create([
            'user_id' => $user->id,
            'type' => 'quantifiable',
            'title' => 'Run a marathon',
            'current_value' => 14,
            'target_value' => 42,
        ]);
        $entry = GoalEntry::factory()->create([
            'goal_id' => $goal->id,
            'previous_value' => 9,
            'value' => 14,
            'entry_date' => '2026-07-26',
        ]);

        Sanctum::actingAs($user, ['read', 'write', 'delete']);

        IgniteServer::tool(DeleteEntryTool::class, ['entry_id' => $entry->id])
            ->assertOk()
            ->assertSee('progress entry of +5 recorded on 2026-07-26')
            ->assertSee("the goal 'Run a marathon'")
            ->assertSee('moving its current value from 14 to 9')
            ->assertStructuredContent(fn (AssertableJson $json) => $json
                ->where('requires_confirmation', true)
                ->has('confirmation_token')
                ->where('preview', fn (string $preview) => str_contains($preview, 'moving its current value from 14 to 9'))
                ->etc());

        $this->assertDatabaseHas('goal_entries', ['id' => $entry->id]);
    }

    public function test_the_preview_for_a_recurring_check_in_says_the_value_is_unaffected(): void
    {
        $user = User::factory()->create();
        $goal = Goal::factory()->create([
            'user_id' => $user->id,
            'type' => 'recurring',
            'recurrence' => 'daily',
            'title' => 'Daily writing',
            'current_value' => 3,
        ]);
        $checkIn = GoalEntry::factory()->create([
            'goal_id' => $goal->id,
            'value' => 1,
            'previous_value' => 0,
            'entry_date' => '2026-07-25',
        ]);

        Sanctum::actingAs($user, ['read', 'write', 'delete']);

        IgniteServer::tool(DeleteEntryTool::class, ['entry_id' => $checkIn->id])
            ->assertOk()
            ->assertSee('check-in recorded on 2026-07-25')
            ->assertSee('current value is not affected');
    }

    public function test_a_confirmed_call_deletes_the_entry_and_rewinds_the_goal_value(): void
    {
        $user = User::factory()->create();
        $goal = Goal::factory()->create([
            'user_id' => $user->id,
            'type' => 'quantifiable',
            'current_value' => 14,
            'target_value' => 42,
        ]);
        $entry = GoalEntry::factory()->create([
            'goal_id' => $goal->id,
            'previous_value' => 9,
            'value' => 14,
        ]);

        Sanctum::actingAs($user, ['read', 'write', 'delete']);

        $token = $this->confirmationTokenFrom(
            IgniteServer::tool(DeleteEntryTool::class, ['entry_id' => $entry->id])
        );

        IgniteServer::tool(DeleteEntryTool::class, [
            'entry_id' => $entry->id,
            'confirmation_token' => $token,
        ])->assertOk();

        $this->assertDatabaseMissing('goal_entries', ['id' => $entry->id]);
        $this->assertSame(9.0, (float) $goal->fresh()->current_value);
    }

    public function test_a_confirmed_call_on_a_recurring_check_in_leaves_the_current_value(): void
    {
        $user = User::factory()->create();
        $goal = Goal::factory()->create([
            'user_id' => $user->id,
            'type' => 'recurring',
            'recurrence' => 'daily',
            'current_value' => 3,
        ]);
        $checkIn = GoalEntry::factory()->create([
            'goal_id' => $goal->id,
            'value' => 1,
            'previous_value' => 0,
        ]);

        Sanctum::actingAs($user, ['read', 'write', 'delete']);

        $token = $this->confirmationTokenFrom(
            IgniteServer::tool(DeleteEntryTool::class, ['entry_id' => $checkIn->id])
        );

        IgniteServer::tool(DeleteEntryTool::class, [
            'entry_id' => $checkIn->id,
            'confirmation_token' => $token,
        ])->assertOk();

        $this->assertDatabaseMissing('goal_entries', ['id' => $checkIn->id]);
        $this->assertSame(3.0, (float) $goal->fresh()->current_value);
    }

    public function test_an_invalid_confirmation_token_does_not_delete(): void
    {
        $user = User::factory()->create();
        $goal = Goal::factory()->create(['user_id' => $user->id]);
        $entry = GoalEntry::factory()->create(['goal_id' => $goal->id]);

        Sanctum::actingAs($user, ['read', 'write', 'delete']);

        IgniteServer::tool(DeleteEntryTool::class, [
            'entry_id' => $entry->id,
            'confirmation_token' => 'not-a-real-token',
        ])->assertHasErrors();

        $this->assertDatabaseHas('goal_entries', ['id' => $entry->id]);
    }

    public function test_a_token_without_the_delete_ability_cannot_use_the_tool(): void
    {
        $user = User::factory()->create();
        $goal = Goal::factory()->create(['user_id' => $user->id]);
        $entry = GoalEntry::factory()->create(['goal_id' => $goal->id]);

        Sanctum::actingAs($user, ['read', 'write']);

        IgniteServer::tool(DeleteEntryTool::class, ['entry_id' => $entry->id])
            ->assertHasErrors();

        $this->assertDatabaseHas('goal_entries', ['id' => $entry->id]);
    }

    public function test_it_denies_deleting_another_users_entry(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $goal = Goal::factory()->create(['user_id' => $owner->id]);
        $entry = GoalEntry::factory()->create(['goal_id' => $goal->id]);

        Sanctum::actingAs($intruder, ['read', 'write', 'delete']);

        IgniteServer::tool(DeleteEntryTool::class, ['entry_id' => $entry->id])
            ->assertHasErrors();

        $this->assertDatabaseHas('goal_entries', ['id' => $entry->id]);
    }
}
