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
                ->has('activeGoalsList', 1)
                ->where('activeGoalsList.0.title', 'Active Goal')
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
                ->has('activeGoalsList.0.milestones', 2)
                ->where('activeGoalsList.0.milestones.0.is_completed', true)
                ->where('activeGoalsList.0.milestones.1.is_completed', false)
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
                ->where('activeGoalsList.0.milestones.0.title', 'First')
                ->where('activeGoalsList.0.milestones.1.title', 'Second')
                ->where('activeGoalsList.0.milestones.2.title', 'Third')
                ->where('activeGoalsList.0.milestones.0.is_completed', true)
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
                ->has('activeGoalsList.0.streak')
                ->where('activeGoalsList.0.streak.current', 3)
                ->where('activeGoalsList.0.streak.unit', 'day')
                ->where('activeGoalsList.0.streak.current_period_satisfied', true)
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
                ->where('activeGoalsList.0.streak', null)
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
}
