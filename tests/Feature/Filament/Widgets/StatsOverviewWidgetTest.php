<?php

namespace Tests\Feature\Filament\Widgets;

use App\Filament\Widgets\StatsOverviewWidget;
use App\Models\Goal;
use App\Models\GoalEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\Concerns\WithAdminRole;
use Tests\TestCase;

class StatsOverviewWidgetTest extends TestCase
{
    use RefreshDatabase;
    use WithAdminRole;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpAdminRole();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_the_stats_widget_renders_with_an_empty_database()
    {
        $admin = User::factory()->withoutTwoFactor()->create();
        $admin->assignRole('admin');

        Livewire::actingAs($admin)
            ->test(StatsOverviewWidget::class)
            ->assertSuccessful();
    }

    public function test_it_computes_the_abandonment_rate()
    {
        $admin = User::factory()->withoutTwoFactor()->create();
        $admin->assignRole('admin');

        // Two abandoned of four total -> rate of 0.5.
        Goal::factory()->count(2)->create(['status' => 'in_progress']);
        Goal::factory()->count(2)->create(['status' => 'abandoned']);

        $expected = round(2 / 4, 2);

        Livewire::actingAs($admin)
            ->test(StatsOverviewWidget::class)
            ->assertSee((string) $expected);
    }

    public function test_it_computes_the_completion_rate()
    {
        $admin = User::factory()->withoutTwoFactor()->create();
        $admin->assignRole('admin');

        // One completed of four total -> rate of 0.25.
        Goal::factory()->count(3)->create(['status' => 'in_progress']);
        Goal::factory()->create(['status' => 'completed']);

        $expected = round(1 / 4, 2);

        Livewire::actingAs($admin)
            ->test(StatsOverviewWidget::class)
            ->assertSee((string) $expected);
    }

    public function test_the_recent_stats_only_count_the_last_week()
    {
        Carbon::setTestNow('2026-07-15 12:00:00');

        $admin = User::factory()->withoutTwoFactor()->create();
        $admin->assignRole('admin');

        $goal = Goal::factory()->create(['user_id' => $admin->id, 'category_id' => null]);

        // Recent records (now) — counted.
        User::factory()->create(); // becomes a "new user"
        Goal::factory()->create(['user_id' => $admin->id, 'category_id' => null]);
        GoalEntry::factory()->create(['goal_id' => $goal->id]);

        // Stale records (10 days ago) — excluded by the recent scope.
        User::factory()->create(['created_at' => Carbon::now()->subDays(10)]);
        Goal::factory()->create(['user_id' => $admin->id, 'category_id' => null, 'created_at' => Carbon::now()->subDays(10)]);
        GoalEntry::factory()->create(['goal_id' => $goal->id, 'created_at' => Carbon::now()->subDays(10)]);

        $html = preg_replace('/\s+/', ' ', strip_tags(
            Livewire::actingAs($admin)->test(StatsOverviewWidget::class)->html()
        ));

        // The first number rendered after each label is that stat's value, so a
        // stale-inclusive count (the total) would fail these. `[^0-9]*` tolerates
        // whatever markup Filament places between the label and the value.
        $this->assertMatchesRegularExpression('/New users[^0-9]*2/', $html);
        $this->assertMatchesRegularExpression('/Goals created[^0-9]*2/', $html);
        $this->assertMatchesRegularExpression('/Entries logged[^0-9]*1/', $html);
    }
}
