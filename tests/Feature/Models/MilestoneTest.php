<?php

namespace Tests\Feature\Models;

use App\Models\Goal;
use App\Models\Milestone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MilestoneTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // RELATIONSHIP TESTS
    // =========================================================================

    public function test_milestone_belongs_to_goal()
    {
        $goal = Goal::factory()->create();
        $milestone = Milestone::factory()->create(['goal_id' => $goal->id]);

        $this->assertDatabaseHas('milestones', [
            'id' => $milestone->id,
            'goal_id' => $goal->id,
        ]);
        $this->assertInstanceOf(Goal::class, $milestone->goal);
        $this->assertEquals($goal->id, $milestone->goal_id);
    }

    // =========================================================================
    // METHOD TESTS
    // =========================================================================

    public function test_is_reached_for_ascending_goal_at_target()
    {
        $goal = Goal::factory()->create([
            'direction' => 'ascending',
            'current_value' => 50,
        ]);
        $milestone = Milestone::factory()->create([
            'goal_id' => $goal->id,
            'target_value' => 50,
        ]);

        $this->assertTrue($milestone->is_reached);
    }

    public function test_is_reached_for_descending_goal_at_target()
    {
        $goal = Goal::factory()->create([
            'direction' => 'descending',
            'current_value' => 10,
        ]);
        $milestone = Milestone::factory()->create([
            'goal_id' => $goal->id,
            'target_value' => 10,
        ]);

        $this->assertTrue($milestone->is_reached);
    }

    public function test_is_not_reached_when_below_target()
    {
        $goal = Goal::factory()->create([
            'direction' => 'ascending',
            'current_value' => 30,
        ]);
        $milestone = Milestone::factory()->create([
            'goal_id' => $goal->id,
            'target_value' => 50,
        ]);

        $this->assertFalse($milestone->is_reached);
    }

    public function test_is_completed_when_completed_at_is_a_past_date()
    {
        $goal = Goal::factory()->create([
            'current_value' => 30,
            'type' => 'multi_step',
        ]);
        $milestone = Milestone::factory()->create([
            'goal_id' => $goal->id,
            'target_value' => 15,
            'completed_at' => now()->subDay(),
        ]);

        $this->assertTrue($milestone->is_completed);
    }

    public function test_is_not_completed_when_completed_at_is_a_future_date()
    {
        $goal = Goal::factory()->create([
            'current_value' => 30,
            'type' => 'multi_step',
        ]);
        $milestone = Milestone::factory()->create([
            'goal_id' => $goal->id,
            'target_value' => 15,
            'completed_at' => now()->addDay(),
        ]);

        $this->assertFalse($milestone->is_completed);
    }

    public function test_is_not_completed_when_completed_at_is_null()
    {
        $goal = Goal::factory()->create([
            'current_value' => 30,
            'type' => 'multi_step',
        ]);
        $milestone = Milestone::factory()->create([
            'goal_id' => $goal->id,
            'target_value' => 15,
            'completed_at' => null,
        ]);

        $this->assertFalse($milestone->is_completed);
    }
}
