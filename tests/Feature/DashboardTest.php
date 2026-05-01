<?php

namespace Tests\Feature;

use App\Models\Goal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
