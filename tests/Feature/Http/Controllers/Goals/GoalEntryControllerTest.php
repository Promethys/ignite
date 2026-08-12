<?php

namespace Tests\Feature\Http\Controllers\Goals;

use App\Models\Goal;
use App\Models\GoalEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
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
            'type' => 'quantifiable',
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

    public function test_the_entries_page_carries_today_in_the_user_timezone()
    {
        Carbon::setTestNow('2026-07-20 02:00:00');

        $this->user->update(['timezone' => 'America/Chicago']);

        $response = $this->actingAs($this->user)
            ->get(route('goals.entries', $this->goal))
            ->assertInertia(fn (Assert $page) => $page->has('today'));

        // 02:00 UTC is still the 19th in Chicago. The check-in modal caps its
        // date field with this value, so a browser-local fallback would offer
        // a date the server then rejects.
        $this->assertSame('2026-07-19', $response->inertiaProps('today'));
    }

    public function test_entries_are_paginated()
    {
        GoalEntry::factory()->count(30)->create([
            'goal_id' => $this->goal->id,
            'entry_date' => now()->subDays(rand(0, 30)),
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('goals.entries', $this->goal))
            ->assertInertia(fn (Assert $page) => $page
                ->component('GoalEntries/Index')
                ->has('entries.data')
            );

        $this->assertEquals($this->goal->id, $response->inertiaProps('goal.id'));
        $this->assertEquals(20, count($response->inertiaProps('entries.data')));
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

        $response = $this->actingAs($this->user)
            ->get(route('goals.entries', ['goal' => $this->goal->id, 'search' => 'Unique progress']))
            ->assertInertia(fn (Assert $page) => $page
                ->component('GoalEntries/Index')
                ->has('entries.data')
            );

        $data = $response->inertiaProps('entries.data');

        $this->assertEquals($this->goal->id, $response->inertiaProps('goal.id'));
        $this->assertEquals(1, count($data));
        $this->assertEquals('Unique progress note', $data[0]['note']);
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

        $response = $this->actingAs($this->user)
            ->get(route('goals.entries', ['goal' => $this->goal->id, 'from' => '2026-03-01']))
            ->assertInertia(fn (Assert $page) => $page
                ->component('GoalEntries/Index')
                ->has('entries.data')
            );

        $data = $response->inertiaProps('entries.data');

        $this->assertEquals($this->goal->id, $response->inertiaProps('goal.id'));
        $this->assertEquals(1, count($data));
        $this->assertTrue(Carbon::parse($data[0]['entry_date'])->gt(Carbon::parse('2026-03-01')));
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

        $response = $this->actingAs($this->user)
            ->get(route('goals.entries', ['goal' => $this->goal->id, 'to' => '2026-02-01']))
            ->assertInertia(fn (Assert $page) => $page
                ->component('GoalEntries/Index')
                ->has('entries.data')
            );

        $data = $response->inertiaProps('entries.data');

        $this->assertEquals($this->goal->id, $response->inertiaProps('goal.id'));
        $this->assertEquals(1, count($data));
        $this->assertTrue(Carbon::parse($data[0]['entry_date'])->lt(Carbon::parse('2026-03-01')));
    }

    // =========================================================================
    // UPDATE (RECURRING CHECK-IN)
    // =========================================================================

    private function recurringGoal(array $overrides = []): Goal
    {
        return Goal::factory()->create(array_merge([
            'user_id' => $this->user->id,
            'type' => 'recurring',
            'recurrence' => 'daily',
            'current_value' => 0,
            'status' => 'in_progress',
            'start_date' => '2026-07-01',
        ], $overrides));
    }

    public function test_a_check_in_is_edited_by_its_date_not_an_increment()
    {
        Carbon::setTestNow('2026-07-16 10:00:00');

        $goal = $this->recurringGoal();
        $entry = GoalEntry::factory()->create([
            'goal_id' => $goal->id,
            'entry_date' => '2026-07-14',
            'value' => 1,
            'previous_value' => 0,
        ]);

        $this->actingAs($this->user)
            ->put(route('goals.entries.update', ['goal' => $goal, 'goalEntry' => $entry]), [
                'entry_date' => '2026-07-15',
                'note' => 'Moved a day later',
            ])
            ->assertSessionHasNoErrors();

        $entry->refresh();

        $this->assertSame('2026-07-15', $entry->entry_date->toDateString());
        $this->assertSame('Moved a day later', $entry->note);
        $this->assertSame(0, (int) $goal->fresh()->current_value);
    }

    public function test_editing_a_check_in_onto_a_taken_period_is_rejected()
    {
        Carbon::setTestNow('2026-07-16 10:00:00');

        $goal = $this->recurringGoal();
        $entry = GoalEntry::factory()->create([
            'goal_id' => $goal->id,
            'entry_date' => '2026-07-14',
            'value' => 1,
            'previous_value' => 0,
        ]);
        GoalEntry::factory()->create([
            'goal_id' => $goal->id,
            'entry_date' => '2026-07-15',
            'value' => 1,
            'previous_value' => 0,
        ]);

        $this->actingAs($this->user)
            ->put(route('goals.entries.update', ['goal' => $goal, 'goalEntry' => $entry]), [
                'entry_date' => '2026-07-15',
            ])
            ->assertSessionHasErrors('entry_date');

        $this->assertSame('2026-07-14', $entry->fresh()->entry_date->toDateString());
    }

    public function test_a_check_in_can_be_saved_on_its_own_date()
    {
        Carbon::setTestNow('2026-07-16 10:00:00');

        $goal = $this->recurringGoal();
        $entry = GoalEntry::factory()->create([
            'goal_id' => $goal->id,
            'entry_date' => '2026-07-14',
            'value' => 1,
            'previous_value' => 0,
            'note' => null,
        ]);

        // The period guard must ignore the entry being edited, otherwise
        // changing only the note would collide with itself.
        $this->actingAs($this->user)
            ->put(route('goals.entries.update', ['goal' => $goal, 'goalEntry' => $entry]), [
                'entry_date' => '2026-07-14',
                'note' => 'Note only',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('Note only', $entry->fresh()->note);
    }

    public function test_a_check_in_cannot_be_moved_to_the_future()
    {
        Carbon::setTestNow('2026-07-16 10:00:00');

        $goal = $this->recurringGoal();
        $entry = GoalEntry::factory()->create([
            'goal_id' => $goal->id,
            'entry_date' => '2026-07-14',
            'value' => 1,
            'previous_value' => 0,
        ]);

        $this->actingAs($this->user)
            ->put(route('goals.entries.update', ['goal' => $goal, 'goalEntry' => $entry]), [
                'entry_date' => '2026-07-17',
            ])
            ->assertSessionHasErrors('entry_date');
    }

    public function test_a_check_in_can_be_moved_before_the_goal_start_date()
    {
        Carbon::setTestNow('2026-07-16 10:00:00');

        $goal = $this->recurringGoal();
        $entry = GoalEntry::factory()->create([
            'goal_id' => $goal->id,
            'entry_date' => '2026-07-14',
            'value' => 1,
            'previous_value' => 0,
        ]);

        $this->actingAs($this->user)
            ->put(route('goals.entries.update', ['goal' => $goal, 'goalEntry' => $entry]), [
                'entry_date' => '2026-06-30',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('2026-06-30', $entry->fresh()->entry_date->toDateString());
    }

    public function test_an_entry_cannot_be_updated_through_another_goal()
    {
        $otherGoal = Goal::factory()->create(['user_id' => $this->user->id]);
        $entry = GoalEntry::factory()->create([
            'goal_id' => $this->goal->id,
            'entry_date' => now(),
        ]);

        $this->actingAs($this->user)
            ->put(route('goals.entries.update', ['goal' => $otherGoal, 'goalEntry' => $entry]), [
                'increment' => 5,
            ])
            ->assertNotFound();
    }

    // =========================================================================
    // STORE (RECURRING CHECK-IN)
    // =========================================================================

    public function test_user_can_check_in_on_a_recurring_goal()
    {
        Carbon::setTestNow('2026-07-16 10:00:00');

        $goal = Goal::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'recurring',
            'recurrence' => 'daily',
            'current_value' => 0,
            'status' => 'in_progress',
            'start_date' => '2026-07-01',
        ]);

        $this->actingAs($this->user)
            ->from(route('goals.show', $goal))
            ->post(route('goals.entries.store', $goal), [
                'entry_date' => '2026-07-16',
            ])
            ->assertRedirectBack()
            ->assertInertiaFlash('toast.type', 'success')
            ->assertInertiaFlash('toast.message', 'Entry saved.');

        $this->assertDatabaseHas('goal_entries', [
            'goal_id' => $goal->id,
            'value' => 1,
            'previous_value' => 0,
        ]);
        $this->assertSame('2026-07-16', GoalEntry::where('goal_id', $goal->id)->sole()->entry_date->toDateString());

        $this->assertSame(0, (int) $goal->fresh()->current_value);
    }

    public function test_recurring_check_in_rejects_a_future_date()
    {
        Carbon::setTestNow('2026-07-16 10:00:00');

        $goal = Goal::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'recurring',
            'recurrence' => 'daily',
            'status' => 'in_progress',
        ]);

        $this->actingAs($this->user)
            ->post(route('goals.entries.store', $goal), [
                'entry_date' => '2026-07-17',
            ])
            ->assertSessionHasErrors('entry_date');

        $this->assertDatabaseMissing('goal_entries', ['goal_id' => $goal->id]);
    }

    public function test_recurring_check_in_allows_a_past_date()
    {
        Carbon::setTestNow('2026-07-16 10:00:00');

        $goal = Goal::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'recurring',
            'recurrence' => 'daily',
            'start_date' => '2026-07-01',
            'status' => 'in_progress',
        ]);

        $this->actingAs($this->user)
            ->from(route('goals.show', $goal))
            ->post(route('goals.entries.store', $goal), [
                'entry_date' => '2026-07-13',
            ])
            ->assertRedirectBack();

        $this->assertSame('2026-07-13', GoalEntry::where('goal_id', $goal->id)->sole()->entry_date->toDateString());
    }

    public function test_recurring_check_in_accepts_a_date_before_start_date()
    {
        Carbon::setTestNow('2026-07-16 10:00:00');

        $goal = Goal::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'recurring',
            'recurrence' => 'daily',
            'start_date' => '2026-07-10',
            'status' => 'in_progress',
        ]);

        $this->actingAs($this->user)
            ->post(route('goals.entries.store', $goal), [
                'entry_date' => '2026-07-05',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('goal_entries', [
            'goal_id' => $goal->id,
            'entry_date' => '2026-07-05 00:00:00',
        ]);
    }

    public function test_recurring_check_in_rejects_a_second_entry_in_the_same_period()
    {
        Carbon::setTestNow('2026-07-16 10:00:00');

        $goal = Goal::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'recurring',
            'recurrence' => 'daily',
            'start_date' => '2026-07-01',
            'status' => 'in_progress',
        ]);

        GoalEntry::factory()->create([
            'goal_id' => $goal->id,
            'entry_date' => '2026-07-16',
            'value' => 1,
            'previous_value' => 0,
        ]);

        $this->actingAs($this->user)
            ->post(route('goals.entries.store', $goal), [
                'entry_date' => '2026-07-16',
            ])
            ->assertSessionHasErrors('entry_date');

        $this->assertSame(1, GoalEntry::where('goal_id', $goal->id)->count());
    }

    public function test_recurring_check_in_rejects_a_second_entry_in_the_same_weekly_period()
    {
        Carbon::setTestNow('2026-07-16 10:00:00');

        $goal = Goal::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'recurring',
            'recurrence' => 'weekly',
            'start_date' => '2026-06-01',
            'status' => 'in_progress',
        ]);

        // 2026-07-13 (Monday) and 2026-07-16 (Thursday) share ISO week 29.
        GoalEntry::factory()->create([
            'goal_id' => $goal->id,
            'entry_date' => '2026-07-13',
            'value' => 1,
            'previous_value' => 0,
        ]);

        $this->actingAs($this->user)
            ->post(route('goals.entries.store', $goal), [
                'entry_date' => '2026-07-16',
            ])
            ->assertSessionHasErrors('entry_date');

        $this->assertSame(1, GoalEntry::where('goal_id', $goal->id)->count());
    }

    public function test_recurring_check_in_does_not_change_current_value()
    {
        Carbon::setTestNow('2026-07-16 10:00:00');

        $goal = Goal::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'recurring',
            'recurrence' => 'daily',
            'current_value' => 0,
            'status' => 'in_progress',
            'start_date' => '2026-07-01',
        ]);

        $this->actingAs($this->user)
            ->post(route('goals.entries.store', $goal), [
                'entry_date' => '2026-07-16',
            ]);

        $this->assertSame(0, (int) $goal->fresh()->current_value);
    }

    public function test_a_relapse_can_be_logged_on_a_negative_recurring_goal()
    {
        Carbon::setTestNow('2026-07-16 10:00:00');

        $goal = Goal::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'recurring',
            'polarity' => 'negative',
            'recurrence' => 'daily',
            'status' => 'in_progress',
            'start_date' => '2026-07-01',
        ]);

        $this->actingAs($this->user)
            ->from(route('goals.show', $goal))
            ->post(route('goals.entries.store', $goal), [
                'entry_date' => '2026-07-16',
            ])
            ->assertRedirectBack();

        $this->assertSame('2026-07-16', GoalEntry::where('goal_id', $goal->id)->sole()->entry_date->toDateString());
    }

    public function test_recurring_check_in_period_guard_uses_the_user_timezone_at_a_month_boundary()
    {
        // The user is in America/Chicago (UTC-5). A check-in dated the 1st of
        // the month must be bucketed into that calendar month by both the guard
        // and the streak. UTC-midnight parsing would shift it into the previous
        // month, letting a second same-month check-in slip through.
        Carbon::setTestNow('2026-07-20 10:00:00');

        $user = User::factory()->create(['timezone' => 'America/Chicago']);

        $goal = Goal::factory()->create([
            'user_id' => $user->id,
            'type' => 'recurring',
            'recurrence' => 'monthly',
            'start_date' => '2026-01-01',
            'status' => 'in_progress',
        ]);

        GoalEntry::factory()->create([
            'goal_id' => $goal->id,
            'entry_date' => '2026-07-01',
            'value' => 1,
            'previous_value' => 0,
        ]);

        $this->actingAs($user)
            ->post(route('goals.entries.store', $goal), [
                'entry_date' => '2026-07-15',
            ])
            ->assertSessionHasErrors('entry_date');

        $this->assertSame(1, GoalEntry::where('goal_id', $goal->id)->count());
    }

    // =========================================================================
    // STORE
    // =========================================================================

    public function test_user_can_add_entry_to_their_goal()
    {
        $this->actingAs($this->user)
            ->from(route('goals.show', $this->goal))
            ->post(route('goals.entries.store', $this->goal), [
                'increment' => 10,
            ])
            ->assertRedirectBack()
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

    public function test_a_note_of_two_thousand_characters_is_accepted()
    {
        $this->actingAs($this->user)
            ->post(route('goals.entries.store', $this->goal), [
                'increment' => 10,
                'note' => str_repeat('a', 2000),
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(2000, strlen($this->goal->entries()->latest('id')->first()->note));
    }

    public function test_a_note_longer_than_two_thousand_characters_is_rejected()
    {
        $this->actingAs($this->user)
            ->post(route('goals.entries.store', $this->goal), [
                'increment' => 10,
                'note' => str_repeat('a', 2001),
            ])
            ->assertSessionHasErrors('note');
    }

    public function test_a_note_keeps_the_line_breaks_the_user_typed()
    {
        $note = "First line\nSecond line\n\nFourth line";

        $this->actingAs($this->user)
            ->post(route('goals.entries.store', $this->goal), [
                'increment' => 10,
                'note' => $note,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame($note, $this->goal->entries()->latest('id')->first()->note);
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
    // UPDATE
    // =========================================================================

    public function test_guest_is_redirected_to_login_when_updating()
    {
        $entry = GoalEntry::factory()->create([
            'goal_id' => $this->goal->id,
            'value' => 10,
            'previous_value' => 0,
        ]);

        $this->put(route('goals.entries.update', [$this->goal, $entry]), ['increment' => 20])
            ->assertRedirect(route('login'));
    }

    public function test_user_can_update_their_entry()
    {
        $entry = GoalEntry::factory()->create([
            'goal_id' => $this->goal->id,
            'value' => 10,
            'previous_value' => 0,
        ]);

        $this->actingAs($this->user)
            ->from(route('goals.entries', $this->goal))
            ->put(route('goals.entries.update', [$this->goal, $entry]), [
                'increment' => 25,
                'note' => 'Updated note',
            ])
            ->assertRedirectBack()
            ->assertInertiaFlash('toast.type', 'success')
            ->assertInertiaFlash('toast.message', 'Entry saved.');

        $this->assertDatabaseHas('goal_entries', [
            'id' => $entry->id,
            'value' => 25,
            'previous_value' => 0,
            'note' => 'Updated note',
        ]);
    }

    public function test_updating_latest_entry_recalculates_goal_current_value()
    {
        $this->goal->update(['current_value' => 30]);

        $entry = GoalEntry::factory()->create([
            'goal_id' => $this->goal->id,
            'value' => 30,
            'previous_value' => 0,
        ]);

        $this->actingAs($this->user)
            ->put(route('goals.entries.update', [$this->goal, $entry]), [
                'increment' => 20,
            ]);

        // current_value = 30 + 20(new increment) - 30(old increment) = 20
        $this->assertEquals(20, $this->goal->fresh()->current_value);
    }

    public function test_updating_historical_entry_recalculates_goal_current_value()
    {
        $this->goal->update(['current_value' => 30]);

        $historicalEntry = GoalEntry::factory()->create([
            'goal_id' => $this->goal->id,
            'value' => 10,
            'previous_value' => 0,
            'entry_date' => now()->subDay(),
        ]);
        GoalEntry::factory()->create([
            'goal_id' => $this->goal->id,
            'value' => 30,
            'previous_value' => 10,
            'entry_date' => now(),
        ]);

        // Edit the historical entry: change increment from 10 to 5
        $this->actingAs($this->user)
            ->put(route('goals.entries.update', [$this->goal, $historicalEntry]), [
                'increment' => 5,
            ]);

        // current_value = 30 + 5(new) - 10(old increment) = 25
        $this->assertEquals(25, $this->goal->fresh()->current_value);
        // entry value = previous_value(0) + 5 = 5
        $this->assertEquals(5, $historicalEntry->fresh()->value);
    }

    public function test_entry_increment_is_required_on_update()
    {
        $entry = GoalEntry::factory()->create([
            'goal_id' => $this->goal->id,
            'value' => 10,
            'previous_value' => 0,
        ]);

        $this->actingAs($this->user)
            ->put(route('goals.entries.update', [$this->goal, $entry]), ['increment' => null])
            ->assertSessionHasErrors('increment');
    }

    public function test_user_cannot_update_other_users_entry()
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
            ->put(route('goals.entries.update', [$otherGoal, $entry]), ['increment' => 20])
            ->assertForbidden();
    }

    // =========================================================================
    // DESTROY
    // =========================================================================

    public function test_user_can_delete_a_recurring_check_in()
    {
        Carbon::setTestNow('2026-07-16 10:00:00');

        $goal = Goal::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'recurring',
            'recurrence' => 'daily',
            'current_value' => 0,
            'status' => 'in_progress',
        ]);

        $entry = GoalEntry::factory()->create([
            'goal_id' => $goal->id,
            'entry_date' => '2026-07-16',
            'value' => 1,
            'previous_value' => 0,
        ]);

        $this->actingAs($this->user)
            ->delete(route('goals.entries.destroy', [$goal, $entry]))
            ->assertRedirect()
            ->assertInertiaFlash('toast.type', 'success')
            ->assertInertiaFlash('toast.message', 'Entry deleted.');

        $this->assertModelMissing($entry);
        $this->assertSame(0, (int) $goal->fresh()->current_value);
    }

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
