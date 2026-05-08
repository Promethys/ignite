<?php

namespace Tests\Feature\Observers;

use App\Models\Goal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GoalObserverTest extends TestCase
{
    use RefreshDatabase;

    public function test_initial_value_is_set_on_goal_creation()
    {
        $goal = Goal::factory()->create([
            'current_value' => 42,
            'initial_value' => null,
        ]);

        $this->assertEquals(42, $goal->fresh()->initial_value);
    }

    public function test_ascending_goal_auto_completes_when_target_reached()
    {
        $goal = Goal::factory()->create([
            'direction' => 'ascending',
            'current_value' => 100,
            'target_value' => 100,
            'status' => 'in_progress',
        ]);

        $this->assertEquals('completed', $goal->fresh()->status);
        $this->assertNotNull($goal->fresh()->completed_at);
    }

    public function test_descending_goal_auto_completes_when_target_reached()
    {
        $goal = Goal::factory()->create([
            'direction' => 'descending',
            'current_value' => 0,
            'target_value' => 0,
            'initial_value' => 100,
            'status' => 'in_progress',
        ]);

        $this->assertEquals('completed', $goal->fresh()->status);
        $this->assertNotNull($goal->fresh()->completed_at);
    }

    public function test_goal_does_not_auto_complete_before_reaching_target()
    {
        $goal = Goal::factory()->quantifiable()->create([
            'current_value' => 50,
            'target_value' => 100,
            'status' => 'in_progress',
            'completed_at' => null
        ]);

        $this->assertEquals('in_progress', $goal->fresh()->status);
        $this->assertNull($goal->fresh()->completed_at);
    }

    public function test_completed_at_timestamp_is_set_on_auto_completion()
    {
        $before = now()->subSecond();

        $goal = Goal::factory()->create([
            'direction' => 'ascending',
            'current_value' => 100,
            'target_value' => 100,
            'status' => 'in_progress',
        ]);

        $this->assertTrue($goal->fresh()->completed_at->greaterThan($before));
    }
}
