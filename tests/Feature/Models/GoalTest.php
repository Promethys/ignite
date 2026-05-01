<?php

namespace Tests\Feature\Models;

use App\Models\Category;
use App\Models\Goal;
use App\Models\GoalEntry;
use App\Models\Milestone;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GoalTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // RELATIONSHIP TESTS
    // =========================================================================

    public function test_goal_belongs_to_user()
    {
        $user = User::factory()->create();
        $goal = Goal::factory()->create(['user_id' => $user->id]);

        $this->assertDatabaseHas('goals', [
            'id' => $goal->id,
            'user_id' => $user->id,
        ]);
        $this->assertInstanceOf(User::class, $goal->user);
        $this->assertEquals($user->id, $goal->user_id);
    }

    public function test_goal_belongs_to_category()
    {
        $category = Category::factory()->create();
        $goal = Goal::factory()->create(['category_id' => $category->id]);

        $this->assertDatabaseHas('goals', [
            'id' => $goal->id,
            'category_id' => $category->id,
        ]);
        $this->assertInstanceOf(Category::class, $goal->category);
        $this->assertEquals($category->id, $goal->category_id);
    }

    public function test_goal_has_many_entries()
    {
        $goal = Goal::factory()->create();
        $entry = GoalEntry::factory()->create(['goal_id' => $goal->id]);

        $this->assertCount(1, $goal->entries);
        $this->assertTrue($goal->entries->contains($entry));
        $this->assertInstanceOf(GoalEntry::class, $goal->entries->first());
    }

    public function test_goal_has_many_milestones()
    {
        $goal = Goal::factory()->create();
        $milestone = Milestone::factory()->create(['goal_id' => $goal->id]);

        $this->assertCount(1, $goal->milestones);
        $this->assertTrue($goal->milestones->contains($milestone));
        $this->assertInstanceOf(Milestone::class, $goal->milestones->first());
    }

    // =========================================================================
    // CAST TESTS
    // =========================================================================

    public function test_goal_casts_current_value_and_target_value_as_decimal()
    {
        $goal = Goal::factory()->quantifiable()->create([
            'current_value' => 25.50,
            'target_value' => 100.00,
        ]);

        $this->assertIsString($goal->current_value);
        $this->assertEquals('25.50', $goal->current_value);
        $this->assertIsString($goal->target_value);
        $this->assertEquals('100.00', $goal->target_value);
    }

    public function test_goal_casts_direction_correctly()
    {
        $ascending = Goal::factory()->create(['direction' => 'ascending']);
        $this->assertEquals('ascending', $ascending->direction);

        $descending = Goal::factory()->create(['direction' => 'descending']);
        $this->assertEquals('descending', $descending->direction);
    }

    public function test_goal_casts_dates_correctly()
    {
        $goal = Goal::factory()->create([
            'start_date' => '2026-01-15',
            'deadline' => '2026-06-30',
            'completed_at' => '2026-03-10 14:30:00',
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $goal->start_date);
        $this->assertEquals('2026-01-15', $goal->start_date->format('Y-m-d'));

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $goal->deadline);
        $this->assertEquals('2026-06-30', $goal->deadline->format('Y-m-d'));

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $goal->completed_at);
        $this->assertEquals('2026-03-10 14:30:00', $goal->completed_at->format('Y-m-d H:i:s'));
    }

    // =========================================================================
    // ACCESSOR TESTS
    // =========================================================================

    public function test_progress_percentage_for_ascending_goal()
    {
        $goal = Goal::factory()->quantifiable()->create([
            'initial_value' => 0,
            'current_value' => 0,
            'target_value' => 100,
        ]);

        $goal->update(['current_value' => 50]);

        $this->assertEquals(50.0, $goal->fresh()->progress_percentage);
    }

    public function test_progress_percentage_for_descending_goal()
    {
        $goal = Goal::factory()->descending()->create([
            'initial_value' => 100,
            'current_value' => 100,
            'target_value' => 0,
        ]);

        $goal->update(['current_value' => 25]);

        $this->assertEquals(75.0, $goal->fresh()->progress_percentage);
    }

    public function test_progress_percentage_is_capped_at_100()
    {
        $goal = Goal::factory()->quantifiable()->create([
            'initial_value' => 0,
            'current_value' => 0,
            'target_value' => 100,
        ]);

        $goal->update(['current_value' => 150]);

        $this->assertEquals(150.0, $goal->fresh()->progress_percentage);
    }

    public function test_is_overdue_returns_true_when_past_deadline()
    {
        $goal = Goal::factory()->create([
            'status' => 'in_progress',
            'deadline' => now()->subDay(),
        ]);

        $this->assertTrue($goal->is_overdue);
    }

    public function test_is_overdue_returns_false_when_no_deadline()
    {
        $goal = Goal::factory()->create([
            'status' => 'in_progress',
            'deadline' => null,
        ]);

        $this->assertFalse($goal->is_overdue);
    }

    public function test_is_overdue_returns_false_when_completed()
    {
        $goal = Goal::factory()->create([
            'status' => 'completed',
            'deadline' => now()->subDay(),
            'completed_at' => now(),
        ]);

        $this->assertFalse($goal->is_overdue);
    }

    public function test_is_completed_returns_true_when_status_is_completed()
    {
        $goal = Goal::factory()->create([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        $this->assertTrue($goal->is_completed);
    }

    // =========================================================================
    // METHOD TESTS
    // =========================================================================

    public function test_mark_as_completed_sets_status_and_timestamp()
    {
        $goal = Goal::factory()->create(['status' => 'in_progress']);

        $goal->markAsCompleted();

        $this->assertEquals('completed', $goal->fresh()->status);
        $this->assertNotNull($goal->fresh()->completed_at);
    }

    public function test_update_status_changes_goal_status()
    {
        $goal = Goal::factory()->create(['status' => 'in_progress']);

        $goal->updateStatus('paused');

        $this->assertEquals('paused', $goal->fresh()->status);
    }
}
