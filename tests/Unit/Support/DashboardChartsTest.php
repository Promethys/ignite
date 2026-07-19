<?php

namespace Tests\Unit\Support;

use App\Models\Category;
use App\Models\Goal;
use App\Models\User;
use App\Support\DashboardCharts;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DashboardChartsTest extends TestCase
{
    use RefreshDatabase;

    private function completedGoal(User $user, string $completedAt): void
    {
        Goal::factory()->create([
            'user_id' => $user->id,
            'type' => 'simple',
            'target_value' => null,
            'current_value' => 0,
            'status' => 'completed',
            'completed_at' => $completedAt,
        ]);
    }

    private function activeGoal(User $user, ?int $categoryId): void
    {
        Goal::factory()->create([
            'user_id' => $user->id,
            'type' => 'simple',
            'target_value' => null,
            'current_value' => 0,
            'status' => 'in_progress',
            'completed_at' => null,
            'category_id' => $categoryId,
        ]);
    }

    public function test_monthly_completions_returns_twelve_zero_filled_months(): void
    {
        Carbon::setTestNow('2026-07-15 12:00:00');
        $user = User::factory()->create(['timezone' => 'UTC']);

        $result = DashboardCharts::monthlyCompletions($user);

        $this->assertCount(12, $result);
        $this->assertSame('2025-08', $result[0]['month']);
        $this->assertSame('2026-07', $result[11]['month']);
        $this->assertSame(0, $result[0]['count']);
    }

    public function test_monthly_completions_counts_per_month_and_ignores_out_of_window(): void
    {
        Carbon::setTestNow('2026-07-15 12:00:00');
        $user = User::factory()->create(['timezone' => 'UTC']);

        $this->completedGoal($user, '2026-07-02 09:00:00');
        $this->completedGoal($user, '2026-07-10 09:00:00');
        $this->completedGoal($user, '2026-05-20 09:00:00');
        $this->completedGoal($user, '2025-06-01 09:00:00'); // before the 12-month window

        $byMonth = collect(DashboardCharts::monthlyCompletions($user))->keyBy('month');

        $this->assertSame(2, $byMonth['2026-07']['count']);
        $this->assertSame(1, $byMonth['2026-05']['count']);
        $this->assertSame(0, $byMonth['2026-06']['count']);
        $this->assertArrayNotHasKey('2025-06', $byMonth->all());
        $this->assertSame(3, $byMonth->sum('count'));
    }

    public function test_monthly_completions_buckets_in_the_user_timezone(): void
    {
        Carbon::setTestNow('2026-07-15 12:00:00');
        $user = User::factory()->create(['timezone' => 'Pacific/Auckland']);

        // 2026-06-30 23:00 UTC is 2026-07-01 in Auckland (UTC+12).
        $this->completedGoal($user, '2026-06-30 23:00:00');

        $byMonth = collect(DashboardCharts::monthlyCompletions($user))->keyBy('month');

        $this->assertSame(1, $byMonth['2026-07']['count']);
        $this->assertSame(0, $byMonth['2026-06']['count']);
    }

    public function test_category_breakdown_groups_active_goals_desc_with_colors(): void
    {
        $user = User::factory()->create();
        $health = Category::factory()->create([
            'user_id' => $user->id, 'name' => 'Health', 'color' => '#ef4444',
        ]);
        $career = Category::factory()->create([
            'user_id' => $user->id, 'name' => 'Career', 'color' => '#3b82f6',
        ]);

        $this->activeGoal($user, $health->id);
        $this->activeGoal($user, $health->id);
        $this->activeGoal($user, $career->id);

        $result = DashboardCharts::categoryBreakdown($user);

        $this->assertSame('Health', $result[0]['name']);
        $this->assertSame(2, $result[0]['count']);
        $this->assertSame('#ef4444', $result[0]['color']);
        $this->assertSame('Career', $result[1]['name']);
        $this->assertSame(1, $result[1]['count']);
    }

    public function test_category_breakdown_collects_null_categories_as_uncategorized(): void
    {
        $user = User::factory()->create();
        $health = Category::factory()->create(['user_id' => $user->id, 'name' => 'Health']);

        $this->activeGoal($user, $health->id);
        $this->activeGoal($user, null);
        $this->activeGoal($user, null);

        $byName = collect(DashboardCharts::categoryBreakdown($user))->keyBy('name');

        $this->assertArrayHasKey('Uncategorized', $byName->all());
        $this->assertSame(2, $byName['Uncategorized']['count']);
        $this->assertSame('#888888', $byName['Uncategorized']['color']);
    }

    public function test_category_breakdown_excludes_non_active_goals(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id]);

        $this->activeGoal($user, $category->id);

        // A completed goal in the same category must not be counted.
        Goal::factory()->create([
            'user_id' => $user->id,
            'type' => 'simple',
            'target_value' => null,
            'current_value' => 0,
            'status' => 'completed',
            'completed_at' => now(),
            'category_id' => $category->id,
        ]);

        $this->assertSame(1, collect(DashboardCharts::categoryBreakdown($user))->sum('count'));
    }
}
