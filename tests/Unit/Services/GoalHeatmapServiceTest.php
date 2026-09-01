<?php

namespace Tests\Unit\Services;

use App\Models\Goal;
use App\Models\GoalEntry;
use App\Models\User;
use App\Services\Goals\GoalHeatmapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class GoalHeatmapServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A Monday, so a daily window starting on `startOfWeek` opens on the same
     * weekday the grid's first column expects.
     */
    private const NOW = '2026-08-31 10:00:00';

    private function recurringGoal(string $recurrence, array $attributes = []): Goal
    {
        $user = User::factory()->create($attributes['user'] ?? []);

        return Goal::factory()->create(array_merge([
            'user_id' => $user->id,
            'type' => 'recurring',
            'recurrence' => $recurrence,
        ], $attributes['goal'] ?? []));
    }

    private function entriesOn(Goal $goal, string ...$dates): void
    {
        foreach ($dates as $date) {
            GoalEntry::factory()->create([
                'goal_id' => $goal->id,
                'entry_date' => $date,
            ]);
        }
    }

    /**
     * @return array<int, string>
     */
    private function litDates(array $heatmap): array
    {
        return collect($heatmap['cells'])
            ->filter(fn (array $cell) => $cell['value'] > 0)
            ->pluck('date')
            ->values()
            ->all();
    }

    public function test_it_returns_null_for_non_recurring_types(): void
    {
        $user = User::factory()->create();

        foreach (['simple', 'quantifiable', 'multi_step'] as $type) {
            $goal = Goal::factory()->create([
                'user_id' => $user->id,
                'type' => $type,
                'recurrence' => null,
            ]);

            $this->assertNull(GoalHeatmapService::for($goal), "Expected null for the {$type} type");
        }
    }

    public function test_it_returns_null_for_a_recurring_goal_without_a_recurrence(): void
    {
        $goal = $this->recurringGoal('daily', ['goal' => ['recurrence' => null]]);

        $this->assertNull(GoalHeatmapService::for($goal));
    }

    public function test_a_daily_window_spans_fifty_three_columns_ending_today(): void
    {
        Carbon::setTestNow(self::NOW);

        $heatmap = GoalHeatmapService::for($this->recurringGoal('daily'));

        $this->assertSame('daily', $heatmap['cadence']);
        $this->assertCount(365, $heatmap['cells']);
        $this->assertSame('2025-09-01', $heatmap['cells'][0]['date']);
        $this->assertSame('2026-08-31', end($heatmap['cells'])['date']);
    }

    public function test_a_daily_window_still_opens_on_a_monday_mid_week(): void
    {
        Carbon::setTestNow('2026-09-02 10:00:00');

        $heatmap = GoalHeatmapService::for($this->recurringGoal('daily'));

        $this->assertSame('2025-09-01', $heatmap['cells'][0]['date']);
        $this->assertSame('2026-09-02', end($heatmap['cells'])['date']);
        $this->assertCount(367, $heatmap['cells']);
        $this->assertSame(
            'Monday',
            Carbon::parse($heatmap['cells'][0]['date'])->format('l')
        );
    }

    public function test_a_weekly_window_holds_fifty_two_mondays(): void
    {
        Carbon::setTestNow(self::NOW);

        $heatmap = GoalHeatmapService::for($this->recurringGoal('weekly'));

        $this->assertCount(52, $heatmap['cells']);
        $this->assertSame('2025-09-08', $heatmap['cells'][0]['date']);
        $this->assertSame('2026-08-31', end($heatmap['cells'])['date']);

        foreach ($heatmap['cells'] as $cell) {
            $this->assertSame('Monday', Carbon::parse($cell['date'])->format('l'));
        }
    }

    public function test_a_monthly_window_holds_twelve_first_of_months(): void
    {
        Carbon::setTestNow(self::NOW);

        $heatmap = GoalHeatmapService::for($this->recurringGoal('monthly'));

        $this->assertCount(12, $heatmap['cells']);
        $this->assertSame('2025-09-01', $heatmap['cells'][0]['date']);
        $this->assertSame('2026-08-01', end($heatmap['cells'])['date']);
    }

    public function test_an_entry_lights_exactly_the_cell_for_its_day(): void
    {
        Carbon::setTestNow(self::NOW);

        $goal = $this->recurringGoal('daily');
        $this->entriesOn($goal, '2026-07-01', '2026-08-31');

        $heatmap = GoalHeatmapService::for($goal);

        $this->assertSame(['2026-07-01', '2026-08-31'], $this->litDates($heatmap));
        $this->assertSame(2, $heatmap['total']);
    }

    public function test_an_entry_outside_the_window_lights_nothing(): void
    {
        Carbon::setTestNow(self::NOW);

        $goal = $this->recurringGoal('daily');
        $this->entriesOn($goal, '2025-08-31');

        $heatmap = GoalHeatmapService::for($goal);

        $this->assertSame([], $this->litDates($heatmap));
        $this->assertSame(0, $heatmap['total']);
    }

    public function test_a_weekly_entry_lights_the_monday_of_its_iso_week(): void
    {
        Carbon::setTestNow(self::NOW);

        $goal = $this->recurringGoal('weekly');
        $this->entriesOn($goal, '2026-08-27');

        $heatmap = GoalHeatmapService::for($goal);

        $this->assertSame(['2026-08-24'], $this->litDates($heatmap));
    }

    public function test_two_dates_in_one_month_light_a_single_monthly_cell(): void
    {
        Carbon::setTestNow(self::NOW);

        $goal = $this->recurringGoal('monthly');
        $this->entriesOn($goal, '2026-07-03', '2026-07-28');

        $heatmap = GoalHeatmapService::for($goal);

        $this->assertSame(['2026-07-01'], $this->litDates($heatmap));
        $this->assertSame(1, $heatmap['total']);
    }

    public function test_a_goal_without_entries_yields_a_full_window_of_empty_cells(): void
    {
        Carbon::setTestNow(self::NOW);

        $heatmap = GoalHeatmapService::for($this->recurringGoal('daily'));

        $this->assertCount(365, $heatmap['cells']);
        $this->assertSame(0, $heatmap['total']);
        $this->assertSame([0], array_values(array_unique(array_column($heatmap['cells'], 'value'))));
    }

    public function test_the_window_ends_on_the_owners_day_not_the_app_default(): void
    {
        Carbon::setTestNow('2026-09-01 02:00:00');

        $goal = $this->recurringGoal('daily', [
            'user' => ['timezone' => 'America/New_York'],
        ]);

        $heatmap = GoalHeatmapService::for($goal);

        $this->assertSame('2026-08-31', end($heatmap['cells'])['date']);
    }

    public function test_an_annual_window_starts_at_the_first_entry_year(): void
    {
        Carbon::setTestNow(self::NOW);

        $goal = $this->recurringGoal('annually', [
            'goal' => ['start_date' => '2025-01-15'],
        ]);
        $this->entriesOn($goal, '2023-04-02', '2026-02-11');

        $heatmap = GoalHeatmapService::for($goal);

        $this->assertSame(
            ['2023-01-01', '2024-01-01', '2025-01-01', '2026-01-01'],
            array_column($heatmap['cells'], 'date')
        );
        $this->assertSame(['2023-01-01', '2026-01-01'], $this->litDates($heatmap));
    }

    public function test_an_annual_window_falls_back_to_the_start_date(): void
    {
        Carbon::setTestNow(self::NOW);

        $goal = $this->recurringGoal('annually', [
            'goal' => ['start_date' => '2024-06-30'],
        ]);

        $heatmap = GoalHeatmapService::for($goal);

        $this->assertSame(
            ['2024-01-01', '2025-01-01', '2026-01-01'],
            array_column($heatmap['cells'], 'date')
        );
    }

    public function test_an_annual_window_never_starts_after_the_current_year(): void
    {
        Carbon::setTestNow(self::NOW);

        $goal = $this->recurringGoal('annually', [
            'goal' => ['start_date' => '2028-01-01'],
        ]);

        $heatmap = GoalHeatmapService::for($goal);

        $this->assertSame(['2026-01-01'], array_column($heatmap['cells'], 'date'));
    }
}
