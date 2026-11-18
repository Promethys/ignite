<?php

namespace Tests\Feature;

use App\Models\Goal;
use App\Models\GoalEntry;
use App\Models\Milestone;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page()
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_visit_the_dashboard()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('dashboard'));
        $response->assertStatus(200);
    }

    public function test_dashboard_passes_correct_active_goal_count()
    {
        $user = User::factory()->create();

        Goal::factory()->count(3)->create([
            'user_id' => $user->id,
            'status' => 'in_progress',
            'completed_at' => null,
            'current_value' => 0,
            'target_value' => 100,
        ]);
        Goal::factory()->create([
            'user_id' => $user->id,
            'status' => 'completed',
            'completed_at' => now(),
            'current_value' => 0,
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('activeGoalsCount', 3)
                ->where('totalGoalsCount', 4)
            );
    }

    public function test_dashboard_passes_correct_completion_rate()
    {
        $user = User::factory()->create();

        Goal::factory()->count(3)->create([
            'user_id' => $user->id,
            'status' => 'in_progress',
            'completed_at' => null,
            'current_value' => 0,
        ]);
        Goal::factory()->create([
            'user_id' => $user->id,
            'status' => 'completed',
            'completed_at' => now(),
            'current_value' => 0,
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('completionRate', 25)
                ->where('completedGoalsCount', 1)
            );
    }

    public function test_dashboard_shows_only_in_progress_goals_in_list()
    {
        $user = User::factory()->create();

        Goal::factory()->create([
            'user_id' => $user->id,
            'status' => 'in_progress',
            'completed_at' => null,
            'title' => 'Active Goal',
            'current_value' => 0,
        ]);
        Goal::factory()->create([
            'user_id' => $user->id,
            'status' => 'completed',
            'completed_at' => now(),
            'title' => 'Completed Goal',
            'current_value' => 0,
        ]);
        Goal::factory()->create([
            'user_id' => $user->id,
            'status' => 'paused',
            'completed_at' => null,
            'title' => 'Paused Goal',
            'current_value' => 0,
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('otherGoals', 1)
                ->where('otherGoals.0.title', 'Active Goal')
            );
    }

    public function test_dashboard_includes_milestones_so_multi_step_cards_can_show_progress()
    {
        $user = User::factory()->create();
        $goal = Goal::factory()->create([
            'user_id' => $user->id,
            'type' => 'multi_step',
            'status' => 'in_progress',
            'completed_at' => null,
            'target_value' => null,
            'current_value' => 0,
        ]);

        Milestone::factory()->for($goal)->create(['order' => 1, 'completed_at' => now()->subDay()]);
        Milestone::factory()->for($goal)->create(['order' => 2, 'completed_at' => null]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('otherGoals.0.milestones', 2)
                ->where('otherGoals.0.milestones.0.is_completed', true)
                ->where('otherGoals.0.milestones.1.is_completed', false)
                ->etc()
            );
    }

    public function test_dashboard_milestones_follow_their_order_not_their_insertion()
    {
        $user = User::factory()->create();
        $goal = Goal::factory()->create([
            'user_id' => $user->id,
            'type' => 'multi_step',
            'status' => 'in_progress',
            'completed_at' => null,
            'target_value' => null,
            'current_value' => 0,
        ]);

        Milestone::factory()->for($goal)->create(['order' => 3, 'completed_at' => null, 'title' => 'Third']);
        Milestone::factory()->for($goal)->create(['order' => 2, 'completed_at' => null, 'title' => 'Second']);
        Milestone::factory()->for($goal)->create(['order' => 1, 'completed_at' => now()->subDay(), 'title' => 'First']);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('otherGoals.0.milestones.0.title', 'First')
                ->where('otherGoals.0.milestones.1.title', 'Second')
                ->where('otherGoals.0.milestones.2.title', 'Third')
                ->where('otherGoals.0.milestones.0.is_completed', true)
                ->etc()
            );
    }

    public function test_dashboard_attaches_streak_to_active_recurring_goals()
    {
        Carbon::setTestNow('2026-07-06 10:00:00');

        $user = User::factory()->create();

        $goal = Goal::factory()->create([
            'user_id' => $user->id,
            'type' => 'recurring',
            'recurrence' => 'daily',
            'status' => 'in_progress',
            'completed_at' => null,
            'title' => 'Daily meditation',
        ]);

        GoalEntry::factory()
            ->count(3)
            ->sequence(
                ['entry_date' => '2026-07-04'],
                ['entry_date' => '2026-07-05'],
                ['entry_date' => '2026-07-06'],
            )
            ->create(['goal_id' => $goal->id]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('otherGoals.0.streak')
                ->where('otherGoals.0.streak.current', 3)
                ->where('otherGoals.0.streak.unit', 'day')
                ->where('otherGoals.0.streak.current_period_satisfied', true)
            );

        Carbon::setTestNow();
    }

    public function test_dashboard_attaches_null_streak_to_non_recurring_goals()
    {
        $user = User::factory()->create();

        Goal::factory()->create([
            'user_id' => $user->id,
            'type' => 'simple',
            'recurrence' => null,
            'status' => 'in_progress',
            'completed_at' => null,
            'title' => 'Simple goal',
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('otherGoals.0.streak', null)
            );
    }

    public function test_dashboard_exposes_the_chart_props()
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('monthlyCompletions', 12)
                ->has('monthlyCompletions.0', fn (Assert $month) => $month
                    ->has('month')
                    ->has('count')
                )
                ->has('categoryBreakdown')
            );
    }

    public function test_dashboard_orders_pins_and_fills_remaining_slots_with_recent_active_goals()
    {
        $user = User::factory()->create();
        $make = function (array $attributes) use ($user) {
            return Goal::factory()->create(array_merge([
                'user_id' => $user->id, 'category_id' => null, 'type' => 'simple',
                'status' => 'in_progress', 'completed_at' => null, 'current_value' => 0,
            ], $attributes));
        };
        $completed = $make(['status' => 'completed', 'completed_at' => now()]);
        $pinned = $make([]);
        Goal::whereKey($completed->id)->toBase()->update(['pin_order' => 2]);
        Goal::whereKey($pinned->id)->toBase()->update(['pin_order' => 1]);
        $active = collect(range(1, 5))->map(fn ($day) => $make(['updated_at' => now()->subDays($day)]));
        $make(['status' => 'paused']);
        Goal::factory()->create(['status' => 'in_progress', 'completed_at' => null]);

        $this->actingAs($user)->get(route('dashboard'))->assertInertia(fn (Assert $page) => $page
            ->has('pinnedGoals', 2)
            ->where('pinnedGoals.0.id', $pinned->id)
            ->where('pinnedGoals.1.id', $completed->id)
            ->has('otherGoals', 4)
            ->where('otherGoals.0.id', $active[0]->id)
            ->where('otherGoals.1.id', $active[1]->id)
            ->where('otherGoals.2.id', $active[2]->id)
            ->where('otherGoals.3.id', $active[3]->id)
            ->where('activeGoalsCount', 6)
        );
    }

    public function test_dashboard_has_no_filler_when_the_pin_limit_is_reached()
    {
        $user = User::factory()->create();
        foreach (range(1, Goal::MAX_PINNED) as $position) {
            $goal = Goal::factory()->create(['user_id' => $user->id, 'category_id' => null, 'type' => 'simple', 'status' => 'in_progress', 'completed_at' => null]);
            Goal::whereKey($goal->id)->toBase()->update(['pin_order' => $position]);
        }
        Goal::factory()->create(['user_id' => $user->id, 'category_id' => null, 'type' => 'simple', 'status' => 'in_progress', 'completed_at' => null]);

        $this->actingAs($user)->get(route('dashboard'))->assertInertia(fn (Assert $page) => $page
            ->has('pinnedGoals', Goal::MAX_PINNED)
            ->has('otherGoals', 0)
            ->where('activeGoalsCount', Goal::MAX_PINNED + 1)
        );
    }
}
