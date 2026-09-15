<?php

namespace Tests\Feature\Mcp\Tools;

use App\Mcp\Servers\IgniteServer;
use App\Mcp\Tools\LogProgressBatchTool;
use App\Models\Goal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LogProgressBatchToolTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_write_token_logs_every_entry_and_shifts_the_goal_value(): void
    {
        $user = User::factory()->create();
        $goal = $this->quantifiableGoal($user);

        Sanctum::actingAs($user, ['read', 'write']);

        IgniteServer::tool(LogProgressBatchTool::class, [
            'goal_id' => $goal->id,
            'entries' => [
                ['increment' => 10, 'entry_date' => '2026-09-01'],
                ['increment' => 5, 'entry_date' => '2026-09-02', 'note' => 'second'],
            ],
        ])->assertOk();

        $this->assertSame(2, $goal->entries()->count());
        $this->assertSame(35.0, (float) $goal->fresh()->current_value);
    }

    public function test_a_token_without_the_write_ability_cannot_use_the_tool(): void
    {
        $user = User::factory()->create();
        $goal = $this->quantifiableGoal($user);

        Sanctum::actingAs($user, ['read']);

        IgniteServer::tool(LogProgressBatchTool::class, [
            'goal_id' => $goal->id,
            'entries' => [['increment' => 5]],
        ])->assertHasErrors();
    }

    public function test_it_denies_a_batch_on_another_users_goal(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $goal = $this->quantifiableGoal($owner);

        Sanctum::actingAs($intruder, ['read', 'write']);

        IgniteServer::tool(LogProgressBatchTool::class, [
            'goal_id' => $goal->id,
            'entries' => [['increment' => 5]],
        ])->assertHasErrors();

        $this->assertSame(0, $goal->entries()->count());
    }

    public function test_it_rejects_a_batch_on_a_recurring_goal(): void
    {
        $user = User::factory()->create();
        $goal = Goal::factory()->create([
            'user_id' => $user->id,
            'type' => 'recurring',
            'recurrence' => 'daily',
        ]);

        Sanctum::actingAs($user, ['read', 'write']);

        IgniteServer::tool(LogProgressBatchTool::class, [
            'goal_id' => $goal->id,
            'entries' => [['increment' => 5]],
        ])->assertHasErrors();
    }

    public function test_the_batch_is_capped_at_two_hundred_entries(): void
    {
        $user = User::factory()->create();
        $goal = $this->quantifiableGoal($user);

        Sanctum::actingAs($user, ['read', 'write']);

        IgniteServer::tool(LogProgressBatchTool::class, [
            'goal_id' => $goal->id,
            'entries' => array_fill(0, 201, ['increment' => 1]),
        ])->assertHasErrors();

        IgniteServer::tool(LogProgressBatchTool::class, [
            'goal_id' => $goal->id,
            'entries' => [],
        ])->assertHasErrors();

        $this->assertSame(0, $goal->entries()->count());
    }

    public function test_one_invalid_entry_saves_nothing(): void
    {
        $user = User::factory()->create();
        $goal = $this->quantifiableGoal($user);

        Sanctum::actingAs($user, ['read', 'write']);

        IgniteServer::tool(LogProgressBatchTool::class, [
            'goal_id' => $goal->id,
            'entries' => [
                ['increment' => 10, 'entry_date' => '2026-09-01'],
                ['increment' => 'not a number'],
            ],
        ])->assertHasErrors();

        $this->assertSame(0, $goal->entries()->count());
        $this->assertSame(20.0, (float) $goal->fresh()->current_value);
    }

    public function test_blank_nested_values_are_normalized(): void
    {
        $user = User::factory()->create();
        $goal = $this->quantifiableGoal($user);

        Sanctum::actingAs($user, ['read', 'write']);

        IgniteServer::tool(LogProgressBatchTool::class, [
            'goal_id' => $goal->id,
            'entries' => [
                ['increment' => 10, 'entry_date' => '   ', 'note' => '  trimmed  '],
            ],
        ])->assertOk();

        $entry = $goal->entries()->sole();

        $this->assertSame('2026-09-15', Carbon::parse($entry->entry_date)->toDateString());
        $this->assertSame('trimmed', $entry->note);
    }

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-09-15 12:00:00');
    }

    private function quantifiableGoal(User $user): Goal
    {
        return Goal::factory()->create([
            'user_id' => $user->id,
            'type' => 'quantifiable',
            'direction' => 'ascending',
            'status' => 'in_progress',
            'completed_at' => null,
            'current_value' => 20,
            'target_value' => 1000,
            'start_date' => '2026-01-01',
        ]);
    }
}
