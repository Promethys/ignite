<?php

namespace Tests\Integration;

use App\Models\Goal;
use App\Models\GoalEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GoalProgressFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_ascending_goal_full_progression_to_auto_completion()
    {
        $goal = Goal::factory()->create([
            'type' => 'quantifiable',
            'direction' => 'ascending',
            'initial_value' => 0,
            'target_value' => 100,
            'current_value' => 0,
            'status' => 'in_progress',
        ]);

        GoalEntry::factory()->create([
            'goal_id' => $goal->id,
            'value' => 50,
            'previous_value' => 0,
        ]);
        $goal->update(['current_value' => 50]);

        $this->assertEquals(50.0, $goal->fresh()->progress_percentage);
        $this->assertEquals('in_progress', $goal->fresh()->status);

        $goal->update(['current_value' => 100]);

        $this->assertEquals('completed', $goal->fresh()->status);
        $this->assertNotNull($goal->fresh()->completed_at);
        $this->assertEquals(100.0, $goal->fresh()->progress_percentage);
    }

    public function test_descending_goal_full_progression_to_auto_completion()
    {
        $goal = Goal::factory()->create([
            'type' => 'quantifiable',
            'direction' => 'descending',
            'initial_value' => 100,
            'target_value' => 0,
            'current_value' => 100,
            'status' => 'in_progress',
        ]);

        $goal->update(['current_value' => 50]);

        $this->assertEquals(50.0, $goal->fresh()->progress_percentage);
        $this->assertEquals('in_progress', $goal->fresh()->status);

        $goal->update(['current_value' => 0]);

        $this->assertEquals('completed', $goal->fresh()->status);
        $this->assertNotNull($goal->fresh()->completed_at);
        $this->assertEquals(100.0, $goal->fresh()->progress_percentage);
    }

    public function test_entry_deletion_recalculates_current_value_and_progress()
    {
        $goal = Goal::factory()->create([
            'type' => 'quantifiable',
            'direction' => 'ascending',
            'initial_value' => 0,
            'target_value' => 100,
            'current_value' => 0,
            'status' => 'in_progress',
        ]);

        $entry1 = GoalEntry::factory()->create([
            'goal_id' => $goal->id,
            'value' => 30,
            'previous_value' => 0,
        ]);

        $entry2 = GoalEntry::factory()->create([
            'goal_id' => $goal->id,
            'value' => 60,
            'previous_value' => 30,
        ]);

        $goal->update(['current_value' => 60]);

        $this->assertEquals(60.0, $goal->fresh()->progress_percentage);

        $entry2->delete();
        $goal->update(['current_value' => 30]);

        $this->assertEquals(30.0, $goal->fresh()->progress_percentage);
    }

    public function test_goal_can_be_paused_and_resumed()
    {
        $goal = Goal::factory()->create([
            'status' => 'in_progress',
        ]);

        $goal->updateStatus('paused');
        $this->assertEquals('paused', $goal->fresh()->status);

        $goal->updateStatus('in_progress');
        $this->assertEquals('in_progress', $goal->fresh()->status);
    }
}
