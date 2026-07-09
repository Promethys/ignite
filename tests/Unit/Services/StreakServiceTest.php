<?php

namespace Tests\Unit\Services;

use App\Models\Goal;
use App\Models\GoalEntry;
use App\Models\User;
use App\Services\StreakService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class StreakServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_handles_daily_recurrence(): void
    {
        Carbon::setTestNow('2026-07-06 10:00:00');

        $user = User::factory()->create();

        $goal = Goal::factory()->create([
            'user_id' => $user->id,
            'type' => 'recurring',
            'recurrence' => 'daily',
        ]);

        GoalEntry::factory()
            ->count(5)
            ->sequence(
                ['entry_date' => '2026-07-01'],
                ['entry_date' => '2026-07-02'],
                ['entry_date' => '2026-07-04'],
                ['entry_date' => '2026-07-05'],
                ['entry_date' => '2026-07-06'],
            )
            ->create([
                'goal_id' => $goal->id,
            ]);

        $streakData = StreakService::for($goal);

        $this->assertEquals(3, $streakData?->current);
        $this->assertEquals(3, $streakData?->longest);
        $this->assertTrue($streakData?->currentPeriodSatisfied);
    }

    public function test_it_handles_weekly_recurrence(): void
    {
        Carbon::setTestNow('2026-07-06 10:00:00');

        $user = User::factory()->create();

        $goal = Goal::factory()->create([
            'user_id' => $user->id,
            'type' => 'recurring',
            'recurrence' => 'weekly',
        ]);

        GoalEntry::factory()
            ->count(4)
            ->sequence(
                ['entry_date' => '2026-06-01'],
                ['entry_date' => '2026-06-22'],
                ['entry_date' => '2026-06-29'],
                ['entry_date' => '2026-07-06'],
            )
            ->create([
                'goal_id' => $goal->id,
            ]);

        $streakData = StreakService::for($goal);

        $this->assertEquals(3, $streakData?->current);
        $this->assertEquals(3, $streakData?->longest);
    }

    public function test_it_handles_monthly_recurrence(): void
    {
        Carbon::setTestNow('2026-07-06 10:00:00');

        $user = User::factory()->create();

        $goal = Goal::factory()->create([
            'user_id' => $user->id,
            'type' => 'recurring',
            'recurrence' => 'monthly',
        ]);

        GoalEntry::factory()
            ->count(5)
            ->sequence(
                ['entry_date' => '2026-01-06'],
                ['entry_date' => '2026-02-06'],
                ['entry_date' => '2026-05-06'],
                ['entry_date' => '2026-06-06'],
                ['entry_date' => '2026-07-06'],
            )
            ->create([
                'goal_id' => $goal->id,
            ]);

        $streakData = StreakService::for($goal);

        $this->assertEquals(3, $streakData?->current);
        $this->assertEquals(3, $streakData?->longest);
    }

    public function test_it_handles_yearly_recurrence(): void
    {
        Carbon::setTestNow('2026-07-06 10:00:00');

        $user = User::factory()->create();

        $goal = Goal::factory()->create([
            'user_id' => $user->id,
            'type' => 'recurring',
            'recurrence' => 'annually',
        ]);

        GoalEntry::factory()
            ->count(5)
            ->sequence(
                ['entry_date' => '2021-07-06'],
                ['entry_date' => '2022-07-06'],
                ['entry_date' => '2024-07-06'],
                ['entry_date' => '2025-07-06'],
                ['entry_date' => '2026-07-06'],
            )
            ->create([
                'goal_id' => $goal->id,
            ]);

        $streakData = StreakService::for($goal);

        $this->assertEquals(3, $streakData?->current);
        $this->assertEquals(3, $streakData?->longest);
    }

    public function test_a_gap_breaks_the_streak()
    {
        Carbon::setTestNow('2026-07-06 10:00:00');

        $user = User::factory()->create();

        $goal = Goal::factory()->create([
            'user_id' => $user->id,
            'type' => 'recurring',
            'recurrence' => 'daily',
        ]);

        GoalEntry::factory()
            ->count(2)
            ->sequence(
                ['entry_date' => '2026-07-04'],
                ['entry_date' => '2026-07-05'],
            )
            ->create([
                'goal_id' => $goal->id,
            ]);

        $streakData = StreakService::for($goal);

        $this->assertEquals(2, $streakData?->current);
    }

    public function test_it_handles_timezones()
    {
        Carbon::setTestNow('2026-07-06 15:00:00');

        $user = User::factory()->create([
            'timezone' => 'Pacific/Kiritimati',
        ]);

        $goal = Goal::factory()->create([
            'user_id' => $user->id,
            'type' => 'recurring',
            'recurrence' => 'daily',
        ]);

        GoalEntry::factory()
            ->count(5)
            ->sequence(
                ['entry_date' => '2026-07-06'],
                ['entry_date' => '2026-07-07'],
            )
            ->create([
                'goal_id' => $goal->id,
            ]);

        $streakData = StreakService::for($goal);

        $this->assertEquals(2, $streakData?->current);
    }

    public function test_it_handles_goal_with_empty_entries()
    {
        Carbon::setTestNow('2026-07-06 15:00:00');

        $user = User::factory()->create();

        $goal = Goal::factory()->create([
            'user_id' => $user->id,
            'type' => 'recurring',
            'recurrence' => 'daily',
        ]);

        $streakData = StreakService::for($goal);

        $this->assertEquals(0, $streakData?->current);
        $this->assertEquals(0, $streakData?->longest);
    }

    public function test_it_handles_a_longer_streak_in_the_past()
    {
        Carbon::setTestNow('2026-07-06 15:00:00');

        $user = User::factory()->create();

        $goal = Goal::factory()->create([
            'user_id' => $user->id,
            'type' => 'recurring',
            'recurrence' => 'daily',
        ]);

        GoalEntry::factory()
            ->count(10)
            ->sequence(
                ['entry_date' => '2026-06-24'],
                ['entry_date' => '2026-06-25'],
                ['entry_date' => '2026-06-26'],
                ['entry_date' => '2026-06-27'],
                ['entry_date' => '2026-06-28'],
                ['entry_date' => '2026-06-29'],
                ['entry_date' => '2026-06-30'],
                ['entry_date' => '2026-07-04'],
                ['entry_date' => '2026-07-05'],
                ['entry_date' => '2026-07-06'],
            )
            ->create([
                'goal_id' => $goal->id,
            ]);

        $streakData = StreakService::for($goal);

        $this->assertEquals(3, $streakData?->current);
        $this->assertEquals(7, $streakData?->longest);
    }

    public function test_current_period_satisfied_is_false_when_today_is_empty()
    {
        Carbon::setTestNow('2026-07-06 10:00:00');

        $user = User::factory()->create();

        $goal = Goal::factory()->create([
            'user_id' => $user->id,
            'type' => 'recurring',
            'recurrence' => 'daily',
        ]);

        GoalEntry::factory()
            ->count(5)
            ->sequence(
                ['entry_date' => '2026-07-04'],
                ['entry_date' => '2026-07-05'],
            )
            ->create([
                'goal_id' => $goal->id,
            ]);

        $streakData = StreakService::for($goal);

        $this->assertEquals(2, $streakData?->current);
        $this->assertEquals(2, $streakData?->longest);
        $this->assertFalse($streakData?->currentPeriodSatisfied);
    }

    public function test_it_counts_multiple_entries_in_the_same_period_as_one()
    {
        Carbon::setTestNow('2026-07-06 10:00:00');

        $user = User::factory()->create();

        $goal = Goal::factory()->create([
            'user_id' => $user->id,
            'type' => 'recurring',
            'recurrence' => 'weekly',
        ]);

        GoalEntry::factory()
            ->count(5)
            ->sequence(
                ['entry_date' => '2026-06-01'],
                ['entry_date' => '2026-06-22'],
                ['entry_date' => '2026-06-29'],
                ['entry_date' => '2026-06-30'],
                ['entry_date' => '2026-07-06'],
            )
            ->create([
                'goal_id' => $goal->id,
            ]);

        $streakData = StreakService::for($goal);

        $this->assertEquals(3, $streakData?->current);
        $this->assertEquals(3, $streakData?->longest);
    }

    public function test_a_non_recurring_goal_does_not_have_streak_data()
    {
        $user = User::factory()->create();

        $goal = Goal::factory()->create([
            'user_id' => $user->id,
            'type' => 'simple',
            'recurrence' => null,
        ]);

        $this->assertNull(StreakService::for($goal));
    }

    public function test_it_handles_a_single_entry()
    {
        Carbon::setTestNow('2026-07-06 10:00:00');

        $user = User::factory()->create();

        $goal = Goal::factory()->create([
            'user_id' => $user->id,
            'type' => 'recurring',
            'recurrence' => 'daily',
        ]);

        GoalEntry::factory()->create([
            'goal_id' => $goal->id,
            'entry_date' => '2026-07-06',
        ]);

        $streakData = StreakService::for($goal);

        $this->assertEquals(1, $streakData?->current);
        $this->assertEquals(1, $streakData?->longest);
    }

    public function test_it_computes_the_streak_regardless_of_entry_insertion_order()
    {
        Carbon::setTestNow('2026-07-06 10:00:00');

        $user = User::factory()->create();

        $goal = Goal::factory()->create([
            'user_id' => $user->id,
            'type' => 'recurring',
            'recurrence' => 'daily',
        ]);

        GoalEntry::factory()
            ->count(3)
            ->sequence(
                ['entry_date' => '2026-07-05'],
                ['entry_date' => '2026-07-04'],
                ['entry_date' => '2026-07-06'],
            )
            ->create([
                'goal_id' => $goal->id,
            ]);

        $streakData = StreakService::for($goal);

        $this->assertEquals(3, $streakData?->current);
        $this->assertEquals(3, $streakData?->longest);
    }
}
