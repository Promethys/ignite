<?php

namespace Tests\Feature\Models;

use App\Models\Category;
use App\Models\Goal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // RELATIONSHIP TESTS
    // =========================================================================

    public function test_user_has_many_goals()
    {
        $user = User::factory()->create();
        $goal = Goal::factory()->create(['user_id' => $user->id]);

        $this->assertCount(1, $user->goals);
        $this->assertTrue($user->goals->contains($goal));
        $this->assertInstanceOf(Goal::class, $user->goals->first());
    }

    public function test_user_has_many_categories()
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id]);

        $this->assertCount(1, $user->categories);
        $this->assertTrue($user->categories->contains($category));
        $this->assertInstanceOf(Category::class, $user->categories->first());
    }

    // =========================================================================
    // METHOD TESTS
    // =========================================================================

    public function test_active_goals_returns_only_in_progress_goals()
    {
        $user = User::factory()->create();
        Goal::factory()->quantifiable()->create(['user_id' => $user->id, 'status' => 'in_progress', 'current_value' => 10, 'target_value' => 100]);
        Goal::factory()->create(['user_id' => $user->id, 'status' => 'completed', 'completed_at' => now(), 'current_value' => 0]);
        Goal::factory()->create(['user_id' => $user->id, 'status' => 'paused', 'current_value' => 0]);

        $activeGoals = $user->activeGoals()->get();

        $this->assertCount(1, $activeGoals);
        $this->assertEquals('in_progress', $activeGoals->first()->status);
    }

    public function test_completed_goals_returns_only_completed_goals()
    {
        $user = User::factory()->create();
        Goal::factory()->create(['user_id' => $user->id, 'status' => 'in_progress', 'current_value' => 0]);
        Goal::factory()->create(['user_id' => $user->id, 'status' => 'completed', 'completed_at' => now(), 'current_value' => 0]);
        Goal::factory()->create(['user_id' => $user->id, 'status' => 'paused', 'current_value' => 0]);

        $completedGoals = $user->completedGoals()->get();

        $this->assertCount(1, $completedGoals);
        $this->assertEquals('completed', $completedGoals->first()->status);
    }
}
