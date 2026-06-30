<?php

namespace Tests\Feature\Http\Controllers\Goals;

use App\Models\Goal;
use App\Models\GoalEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class GoalEntryControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private User $otherUser;

    private Goal $goal;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->otherUser = User::factory()->create();
        $this->goal = Goal::factory()->create([
            'user_id' => $this->user->id,
            'current_value' => 0,
            'target_value' => 100,
            'direction' => 'ascending',
            'initial_value' => 0,
            'status' => 'in_progress',
        ]);
    }

    // =========================================================================
    // AUTHORIZATION
    // =========================================================================

    public function test_guest_is_redirected_to_login()
    {
        $this->get(route('goals.entries', $this->goal))->assertRedirect(route('login'));
        $this->post(route('goals.entries.store', $this->goal), ['increment' => 10])->assertRedirect(route('login'));
    }

    public function test_user_cannot_view_entries_of_other_users_goal()
    {
        $otherGoal = Goal::factory()->create([
            'user_id' => $this->otherUser->id,
            'current_value' => 0,
        ]);

        $this->actingAs($this->user)
            ->get(route('goals.entries', $otherGoal))
            ->assertForbidden();
    }

    // =========================================================================
    // INDEX
    // =========================================================================

    public function test_user_can_view_entries_for_their_goal()
    {
        $this->actingAs($this->user)
            ->get(route('goals.entries', $this->goal))
            ->assertInertia(fn (Assert $page) => $page
                ->component('GoalEntries/Index')
                ->has('goal')
                ->has('entries')
            );
    }

    public function test_entries_are_paginated()
    {
        GoalEntry::factory()->count(25)->create([
            'goal_id' => $this->goal->id,
            'entry_date' => now()->subDays(rand(0, 30)),
        ]);

        $this->actingAs($this->user)
            ->get(route('goals.entries', $this->goal))
            ->assertInertia(fn (Assert $page) => $page
                ->component('GoalEntries/Index')
                ->has('entries')
            );
    }

    public function test_entries_can_be_searched_by_note()
    {
        GoalEntry::factory()->create([
            'goal_id' => $this->goal->id,
            'note' => 'Unique progress note',
            'entry_date' => now(),
        ]);
        GoalEntry::factory()->create([
            'goal_id' => $this->goal->id,
            'note' => 'Something completely different',
            'entry_date' => now(),
        ]);

        $this->actingAs($this->user)
            ->get(route('goals.entries', array_merge(['goal' => $this->goal->id], ['search' => 'Unique progress'])))
            ->assertInertia(fn (Assert $page) => $page
                ->component('GoalEntries/Index')
                ->has('entries')
            );
    }

    public function test_entries_can_be_filtered_by_date_from()
    {
        GoalEntry::factory()->create([
            'goal_id' => $this->goal->id,
            'entry_date' => '2026-01-15',
        ]);
        GoalEntry::factory()->create([
            'goal_id' => $this->goal->id,
            'entry_date' => '2026-03-15',
        ]);

        $this->actingAs($this->user)
            ->get(route('goals.entries', array_merge(['goal' => $this->goal->id], ['from' => '2026-03-01'])))
            ->assertInertia(fn (Assert $page) => $page
                ->component('GoalEntries/Index')
                ->has('entries')
            );
    }

    public function test_entries_can_be_filtered_by_date_to()
    {
        GoalEntry::factory()->create([
            'goal_id' => $this->goal->id,
            'entry_date' => '2026-01-15',
        ]);
        GoalEntry::factory()->create([
            'goal_id' => $this->goal->id,
            'entry_date' => '2026-03-15',
        ]);

        $this->actingAs($this->user)
            ->get(route('goals.entries', array_merge(['goal' => $this->goal->id], ['to' => '2026-02-01'])))
            ->assertInertia(fn (Assert $page) => $page
                ->component('GoalEntries/Index')
                ->has('entries')
            );
    }

    // =========================================================================
    // STORE
    // =========================================================================

    public function test_user_can_add_entry_to_their_goal()
    {
        $this->actingAs($this->user)
            ->post(route('goals.entries.store', $this->goal), [
                'increment' => 10,
            ])
            ->assertRedirect(route('goals.show', $this->goal))
            ->assertInertiaFlash('toast.type', 'success')
            ->assertInertiaFlash('toast.message', 'Entry saved.');

        $this->assertDatabaseHas('goal_entries', [
            'goal_id' => $this->goal->id,
            'value' => 10,
            'previous_value' => 0,
        ]);
    }

    public function test_entry_value_is_required()
    {
        $this->actingAs($this->user)
            ->post(route('goals.entries.store', $this->goal), [
                'increment' => null,
            ])
            ->assertSessionHasErrors('increment');
    }

    public function test_adding_entry_updates_goal_current_value()
    {
        $this->actingAs($this->user)
            ->post(route('goals.entries.store', $this->goal), [
                'increment' => 25,
            ]);

        $this->assertEquals(25, $this->goal->fresh()->current_value);
    }

    public function test_user_cannot_add_entry_to_other_users_goal()
    {
        $otherGoal = Goal::factory()->create([
            'user_id' => $this->otherUser->id,
            'current_value' => 0,
        ]);

        $this->actingAs($this->user)
            ->post(route('goals.entries.store', $otherGoal), [
                'increment' => 10,
            ])
            ->assertForbidden();
    }

    // =========================================================================
    // DESTROY
    // =========================================================================

    public function test_user_can_delete_their_entry()
    {
        $entry = GoalEntry::factory()->create([
            'goal_id' => $this->goal->id,
            'value' => 10,
            'previous_value' => 0,
        ]);

        $this->actingAs($this->user)
            ->delete(route('goals.entries.destroy', [$this->goal, $entry]))
            ->assertRedirect()
            ->assertInertiaFlash('toast.type', 'success')
            ->assertInertiaFlash('toast.message', 'Entry deleted.');

        $this->assertModelMissing($entry);
    }

    public function test_deleting_entry_recalculates_goal_current_value()
    {
        $this->goal->update(['current_value' => 30]);

        $entry = GoalEntry::factory()->create([
            'goal_id' => $this->goal->id,
            'value' => 30,
            'previous_value' => 0,
        ]);

        $this->actingAs($this->user)
            ->delete(route('goals.entries.destroy', [$this->goal, $entry]));

        $this->assertEquals(0, $this->goal->fresh()->current_value);
    }

    public function test_user_cannot_delete_other_users_entry()
    {
        $otherGoal = Goal::factory()->create([
            'user_id' => $this->otherUser->id,
            'current_value' => 10,
        ]);
        $entry = GoalEntry::factory()->create([
            'goal_id' => $otherGoal->id,
            'value' => 10,
            'previous_value' => 0,
        ]);

        $this->actingAs($this->user)
            ->delete(route('goals.entries.destroy', [$otherGoal, $entry]))
            ->assertForbidden();
    }
}
