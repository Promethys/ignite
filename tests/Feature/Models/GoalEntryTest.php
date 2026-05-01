<?php

namespace Tests\Feature\Models;

use App\Models\Goal;
use App\Models\GoalEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GoalEntryTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // RELATIONSHIP TESTS
    // =========================================================================

    public function test_entry_belongs_to_goal()
    {
        $goal = Goal::factory()->create();
        $entry = GoalEntry::factory()->create(['goal_id' => $goal->id]);

        $this->assertDatabaseHas('goal_entries', [
            'id' => $entry->id,
            'goal_id' => $goal->id,
        ]);
        $this->assertInstanceOf(Goal::class, $entry->goal);
        $this->assertEquals($goal->id, $entry->goal_id);
    }

    // =========================================================================
    // CAST TESTS
    // =========================================================================

    public function test_entry_casts_value_as_decimal()
    {
        $entry = GoalEntry::factory()->create(['value' => 42.50]);

        $this->assertIsString($entry->value);
        $this->assertEquals('42.50', $entry->value);
    }

    public function test_entry_casts_entry_date_as_date()
    {
        $entry = GoalEntry::factory()->create(['entry_date' => '2026-03-15']);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $entry->entry_date);
        $this->assertEquals('2026-03-15', $entry->entry_date->format('Y-m-d'));
    }

    // =========================================================================
    // ACCESSOR TESTS
    // =========================================================================

    public function test_increment_value_returns_value_for_first_entry()
    {
        $entry = GoalEntry::factory()->create([
            'value' => 10.00,
            'previous_value' => 0,
        ]);

        $this->assertEquals(10.0, $entry->increment_value);
    }

    public function test_increment_value_returns_difference_from_previous_entry()
    {
        $entry = GoalEntry::factory()->create([
            'value' => 30.00,
            'previous_value' => 20.00,
        ]);

        $this->assertEquals(10.0, $entry->increment_value);
    }

    public function test_increment_value_has_no_floating_point_errors()
    {
        $entry = GoalEntry::factory()->create([
            'value' => 0.30,
            'previous_value' => 0.20,
        ]);

        $this->assertEquals(0.10, $entry->increment_value);
    }
}
