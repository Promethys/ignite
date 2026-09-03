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
     * A Monday, so a window opening on `startOfWeek` opens on the same weekday
     * the grid's first column expects.
     */
    private const NOW = '2026-08-31 10:00:00';

    /**
     * @param  array{user?: array<string, mixed>, goal?: array<string, mixed>}  $attributes
     */
    private function recurringGoal(string $recurrence, array $attributes = []): Goal
    {
        $user = User::factory()->create($attributes['user'] ?? []);

        return Goal::factory()->create(array_merge([
            'user_id' => $user->id,
            'type' => 'recurring',
            'recurrence' => $recurrence,
            'start_date' => self::NOW,
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
     * @param  array{cells: array<int, array{date: string, value: int}>}  $heatmap
     * @return array<int, string>
     */
    private function dates(array $heatmap): array
    {
        return array_column($heatmap['cells'], 'date');
    }

    /**
     * @param  array{cells: array<int, array{date: string, value: int}>}  $heatmap
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

    // =========================================================================
    // Applicability
    // =========================================================================

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

    // =========================================================================
    // Where the window opens
    // =========================================================================

    public function test_a_short_history_opens_at_the_week_of_the_first_entry(): void
    {
        Carbon::setTestNow(self::NOW);

        $goal = $this->recurringGoal('daily', ['goal' => ['start_date' => '2026-07-01']]);
        $this->entriesOn($goal, '2026-08-01', '2026-08-20');

        $heatmap = GoalHeatmapService::for($goal);

        // 1 August falls in the week opening Monday 27 July.
        $this->assertSame('2026-07-27', $heatmap['cells'][0]['date']);
        $this->assertSame('2026-08-31', end($heatmap['cells'])['date']);
        $this->assertCount(36, $heatmap['cells']);
    }

    public function test_a_history_longer_than_a_year_slides_to_a_rolling_window(): void
    {
        Carbon::setTestNow(self::NOW);

        $goal = $this->recurringGoal('daily', ['goal' => ['start_date' => '2024-01-01']]);
        $this->entriesOn($goal, '2024-01-15', '2026-08-20');

        $heatmap = GoalHeatmapService::for($goal);

        $this->assertSame('2025-09-01', $heatmap['cells'][0]['date']);
        $this->assertSame('2026-08-31', end($heatmap['cells'])['date']);
        $this->assertCount(365, $heatmap['cells']);
    }

    public function test_the_window_opens_on_the_entry_date_not_the_row_creation_date(): void
    {
        Carbon::setTestNow(self::NOW);

        $goal = $this->recurringGoal('daily');

        // Written today, but backdated by the user to thirteen weeks ago.
        $this->entriesOn($goal, '2026-06-01');

        $heatmap = GoalHeatmapService::for($goal);

        $this->assertSame('2026-06-01', $heatmap['cells'][0]['date']);
        $this->assertCount(92, $heatmap['cells']);
    }

    public function test_a_goal_without_entries_opens_at_its_start_date(): void
    {
        Carbon::setTestNow(self::NOW);

        $goal = $this->recurringGoal('daily', ['goal' => ['start_date' => '2026-08-10']]);

        $heatmap = GoalHeatmapService::for($goal);

        $this->assertSame('2026-08-10', $heatmap['cells'][0]['date']);
        $this->assertSame('2026-08-31', end($heatmap['cells'])['date']);
        $this->assertCount(22, $heatmap['cells']);
        $this->assertSame(0, $heatmap['total']);
    }

    public function test_a_goal_without_entries_or_a_start_date_falls_back_to_its_creation(): void
    {
        Carbon::setTestNow('2026-08-12 10:00:00');

        $goal = $this->recurringGoal('daily', ['goal' => [
            'start_date' => null,
            'created_at' => '2026-08-03 09:00:00',
        ]]);

        $heatmap = GoalHeatmapService::for($goal);

        $this->assertSame('2026-08-03', $heatmap['cells'][0]['date']);
        $this->assertSame('2026-08-12', end($heatmap['cells'])['date']);
        $this->assertCount(10, $heatmap['cells']);
    }

    public function test_a_future_start_date_still_yields_a_window(): void
    {
        Carbon::setTestNow(self::NOW);

        $goal = $this->recurringGoal('daily', ['goal' => ['start_date' => '2028-01-01']]);

        $heatmap = GoalHeatmapService::for($goal);

        $this->assertNotEmpty($heatmap['cells']);
        $this->assertSame(['2026-08-31'], $this->dates($heatmap));
    }

    // =========================================================================
    // Cadence geometry
    // =========================================================================

    public function test_a_weekly_window_opens_on_the_week_of_the_first_entry(): void
    {
        Carbon::setTestNow(self::NOW);

        $goal = $this->recurringGoal('weekly', ['goal' => ['start_date' => '2026-07-01']]);
        $this->entriesOn($goal, '2026-08-01');

        $heatmap = GoalHeatmapService::for($goal);

        $this->assertSame(
            ['2026-07-27', '2026-08-03', '2026-08-10', '2026-08-17', '2026-08-24', '2026-08-31'],
            $this->dates($heatmap)
        );
    }

    public function test_a_weekly_window_caps_at_fifty_two_mondays(): void
    {
        Carbon::setTestNow(self::NOW);

        $goal = $this->recurringGoal('weekly', ['goal' => ['start_date' => '2023-01-01']]);
        $this->entriesOn($goal, '2023-02-06');

        $heatmap = GoalHeatmapService::for($goal);

        $this->assertCount(52, $heatmap['cells']);
        $this->assertSame('2025-09-08', $heatmap['cells'][0]['date']);
        $this->assertSame('2026-08-31', end($heatmap['cells'])['date']);

        foreach ($heatmap['cells'] as $cell) {
            $this->assertSame('Monday', Carbon::parse($cell['date'])->format('l'));
        }
    }

    public function test_a_monthly_window_opens_on_the_month_of_the_first_entry(): void
    {
        Carbon::setTestNow(self::NOW);

        $goal = $this->recurringGoal('monthly', ['goal' => ['start_date' => '2026-01-01']]);
        $this->entriesOn($goal, '2026-06-15');

        $heatmap = GoalHeatmapService::for($goal);

        $this->assertSame(['2026-06-01', '2026-07-01', '2026-08-01'], $this->dates($heatmap));
    }

    public function test_a_monthly_window_caps_at_twelve_months(): void
    {
        Carbon::setTestNow(self::NOW);

        $goal = $this->recurringGoal('monthly', ['goal' => ['start_date' => '2023-01-01']]);
        $this->entriesOn($goal, '2023-03-04');

        $heatmap = GoalHeatmapService::for($goal);

        $this->assertCount(12, $heatmap['cells']);
        $this->assertSame('2025-09-01', $heatmap['cells'][0]['date']);
        $this->assertSame('2026-08-01', end($heatmap['cells'])['date']);
    }

    public function test_a_daily_window_still_opens_on_a_monday_mid_week(): void
    {
        Carbon::setTestNow('2026-09-02 10:00:00');

        $goal = $this->recurringGoal('daily', ['goal' => ['start_date' => '2024-01-01']]);
        $this->entriesOn($goal, '2024-05-05');

        $heatmap = GoalHeatmapService::for($goal);

        $this->assertSame('2025-09-01', $heatmap['cells'][0]['date']);
        $this->assertSame('2026-09-02', end($heatmap['cells'])['date']);
        $this->assertCount(367, $heatmap['cells']);
        $this->assertSame('Monday', Carbon::parse($heatmap['cells'][0]['date'])->format('l'));
    }

    // =========================================================================
    // Bucketing and totals
    // =========================================================================

    public function test_an_entry_lights_exactly_the_cell_for_its_day(): void
    {
        Carbon::setTestNow(self::NOW);

        $goal = $this->recurringGoal('daily', ['goal' => ['start_date' => '2026-06-01']]);
        $this->entriesOn($goal, '2026-07-01', '2026-08-31');

        $heatmap = GoalHeatmapService::for($goal);

        $this->assertSame(['2026-07-01', '2026-08-31'], $this->litDates($heatmap));
        $this->assertSame(2, $heatmap['total']);
    }

    public function test_an_entry_outside_the_rolling_window_lights_nothing(): void
    {
        Carbon::setTestNow(self::NOW);

        $goal = $this->recurringGoal('daily', ['goal' => ['start_date' => '2024-01-01']]);
        $this->entriesOn($goal, '2024-06-01', '2024-07-01');

        $heatmap = GoalHeatmapService::for($goal);

        // The history is long, so the window slides and leaves both behind.
        $this->assertSame('2025-09-01', $heatmap['cells'][0]['date']);
        $this->assertSame([], $this->litDates($heatmap));
        $this->assertSame(0, $heatmap['total']);
    }

    public function test_a_weekly_entry_lights_the_monday_of_its_iso_week(): void
    {
        Carbon::setTestNow(self::NOW);

        $goal = $this->recurringGoal('weekly', ['goal' => ['start_date' => '2026-08-01']]);
        $this->entriesOn($goal, '2026-08-27');

        $heatmap = GoalHeatmapService::for($goal);

        $this->assertSame(['2026-08-24'], $this->litDates($heatmap));
    }

    public function test_two_dates_in_one_month_light_a_single_monthly_cell(): void
    {
        Carbon::setTestNow(self::NOW);

        $goal = $this->recurringGoal('monthly', ['goal' => ['start_date' => '2026-07-01']]);
        $this->entriesOn($goal, '2026-07-03', '2026-07-28');

        $heatmap = GoalHeatmapService::for($goal);

        $this->assertSame(['2026-07-01'], $this->litDates($heatmap));
        $this->assertSame(1, $heatmap['total']);
    }

    public function test_the_window_ends_on_the_owners_day_not_the_app_default(): void
    {
        Carbon::setTestNow('2026-09-01 02:00:00');

        $goal = $this->recurringGoal('daily', [
            'user' => ['timezone' => 'America/New_York'],
            'goal' => ['start_date' => '2024-01-01'],
        ]);
        $this->entriesOn($goal, '2024-06-01');

        $heatmap = GoalHeatmapService::for($goal);

        $this->assertSame('2026-08-31', end($heatmap['cells'])['date']);
    }

    // =========================================================================
    // Annual, which has no rolling bound
    // =========================================================================

    public function test_an_annual_window_starts_at_the_first_entry_year(): void
    {
        Carbon::setTestNow(self::NOW);

        $goal = $this->recurringGoal('annually', ['goal' => ['start_date' => '2025-01-15']]);
        $this->entriesOn($goal, '2023-04-02', '2026-02-11');

        $heatmap = GoalHeatmapService::for($goal);

        $this->assertSame(
            ['2023-01-01', '2024-01-01', '2025-01-01', '2026-01-01'],
            $this->dates($heatmap)
        );
        $this->assertSame(['2023-01-01', '2026-01-01'], $this->litDates($heatmap));
    }

    public function test_an_annual_window_falls_back_to_the_start_date(): void
    {
        Carbon::setTestNow(self::NOW);

        $goal = $this->recurringGoal('annually', ['goal' => ['start_date' => '2024-06-30']]);

        $heatmap = GoalHeatmapService::for($goal);

        $this->assertSame(['2024-01-01', '2025-01-01', '2026-01-01'], $this->dates($heatmap));
    }

    public function test_an_annual_window_never_starts_after_the_current_year(): void
    {
        Carbon::setTestNow(self::NOW);

        $goal = $this->recurringGoal('annually', ['goal' => ['start_date' => '2028-01-01']]);

        $heatmap = GoalHeatmapService::for($goal);

        $this->assertSame(['2026-01-01'], $this->dates($heatmap));
    }
}
