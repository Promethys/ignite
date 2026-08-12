<?php

namespace Tests\Feature\Mcp\Tools;

use App\Mcp\Servers\IgniteServer;
use App\Mcp\Tools\LogProgressTool;
use App\Models\Goal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LogProgressDatedTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-08-12 10:00:00');

        $this->user = User::factory()->create(['timezone' => 'UTC']);
        Sanctum::actingAs($this->user, ['read', 'write']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function goal(array $attributes = []): Goal
    {
        return Goal::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'quantifiable',
            'initial_value' => 0,
            'current_value' => 0,
            'target_value' => 100,
            ...$attributes,
        ]);
    }

    public function test_an_entry_without_a_date_is_dated_today(): void
    {
        $goal = $this->goal();

        IgniteServer::tool(LogProgressTool::class, [
            'goal_id' => $goal->id,
            'increment' => 5,
        ])->assertOk();

        $this->assertSame('2026-08-12', $goal->entries()->sole()->entry_date->toDateString());
    }

    public function test_an_entry_can_be_dated_in_the_past(): void
    {
        $goal = $this->goal();

        IgniteServer::tool(LogProgressTool::class, [
            'goal_id' => $goal->id,
            'increment' => 5,
            'entry_date' => '2026-06-01',
        ])->assertOk();

        $entry = $goal->entries()->sole();

        $this->assertSame('2026-06-01', $entry->entry_date->toDateString());
        $this->assertSame(5.0, (float) $goal->fresh()->current_value);
    }

    public function test_an_entry_may_predate_the_goal_start_date(): void
    {
        $goal = $this->goal(['start_date' => '2026-08-01']);

        IgniteServer::tool(LogProgressTool::class, [
            'goal_id' => $goal->id,
            'increment' => 3,
            'entry_date' => '2026-05-20',
        ])->assertOk();

        $this->assertSame('2026-05-20', $goal->entries()->sole()->entry_date->toDateString());
    }

    public function test_a_future_entry_date_is_rejected(): void
    {
        $goal = $this->goal();

        IgniteServer::tool(LogProgressTool::class, [
            'goal_id' => $goal->id,
            'increment' => 5,
            'entry_date' => '2026-08-13',
        ])->assertHasErrors();

        $this->assertSame(0, $goal->entries()->count());
    }

    public function test_the_upper_bound_follows_the_owner_timezone(): void
    {
        Carbon::setTestNow('2026-08-12 03:00:00');
        $this->user->update(['timezone' => 'America/Los_Angeles']);

        $goal = $this->goal();

        IgniteServer::tool(LogProgressTool::class, [
            'goal_id' => $goal->id,
            'increment' => 5,
            'entry_date' => '2026-08-12',
        ])->assertHasErrors();

        $this->assertSame(0, $goal->entries()->count());
    }

    public function test_entries_logged_out_of_order_keep_a_rising_running_total(): void
    {
        $goal = $this->goal();

        foreach ([['2026-07-01', 10], ['2026-05-01', 4], ['2026-06-01', 6]] as [$date, $increment]) {
            IgniteServer::tool(LogProgressTool::class, [
                'goal_id' => $goal->id,
                'increment' => $increment,
                'entry_date' => $date,
            ])->assertOk();
        }

        $chain = $goal->entries()
            ->orderBy('entry_date')
            ->get()
            ->map(fn ($entry) => [
                $entry->entry_date->toDateString(),
                (float) $entry->previous_value,
                (float) $entry->value,
            ])
            ->all();

        $this->assertSame([
            ['2026-05-01', 0.0, 4.0],
            ['2026-06-01', 4.0, 10.0],
            ['2026-07-01', 10.0, 20.0],
        ], $chain);

        $this->assertSame(20.0, (float) $goal->fresh()->current_value);
    }

    public function test_a_backdated_entry_keeps_each_entry_own_increment(): void
    {
        $goal = $this->goal();

        IgniteServer::tool(LogProgressTool::class, [
            'goal_id' => $goal->id,
            'increment' => 10,
            'entry_date' => '2026-07-01',
        ])->assertOk();

        IgniteServer::tool(LogProgressTool::class, [
            'goal_id' => $goal->id,
            'increment' => 4,
            'entry_date' => '2026-06-01',
        ])->assertOk();

        $increments = $goal->entries()
            ->orderBy('entry_date')
            ->get()
            ->map(fn ($entry) => (float) $entry->increment_value)
            ->all();

        $this->assertSame([4.0, 10.0], $increments);
    }
}
