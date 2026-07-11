<?php

namespace Tests\Feature\Http\Controllers\Goals;

use App\Models\Category;
use App\Models\Goal;
use App\Models\GoalEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class GoalControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private User $otherUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->otherUser = User::factory()->create();
    }

    private function validGoalData(array $overrides = []): array
    {
        return array_merge([
            'user_id' => $this->user->id,
            'title' => 'Test Goal',
            'type' => 'simple',
            'direction' => 'ascending',
            'current_value' => 0,
            'status' => 'not_started',
            'priority' => 'medium',
            'points' => 0,
            'is_public' => false,
        ], $overrides);
    }

    // =========================================================================
    // AUTHORIZATION
    // =========================================================================

    public function test_guest_is_redirected_to_login()
    {
        $this->get(route('goals.index'))->assertRedirect(route('login'));
        $this->post(route('goals.store'), $this->validGoalData())->assertRedirect(route('login'));
    }

    public function test_user_cannot_view_other_users_goal()
    {
        $goal = Goal::factory()->create(['user_id' => $this->otherUser->id, 'current_value' => 0]);

        $this->actingAs($this->user)
            ->get(route('goals.show', $goal))
            ->assertForbidden();
    }

    public function test_user_cannot_edit_other_users_goal()
    {
        $goal = Goal::factory()->create(['user_id' => $this->otherUser->id, 'current_value' => 0]);

        $this->actingAs($this->user)
            ->get(route('goals.edit', $goal))
            ->assertForbidden();
    }

    public function test_user_cannot_delete_other_users_goal()
    {
        $goal = Goal::factory()->create(['user_id' => $this->otherUser->id, 'current_value' => 0]);

        $this->actingAs($this->user)
            ->delete(route('goals.destroy', $goal))
            ->assertForbidden();
    }

    public function test_user_cannot_update_status_of_other_users_goal()
    {
        $goal = Goal::factory()->create(['user_id' => $this->otherUser->id, 'current_value' => 0]);

        $this->actingAs($this->user)
            ->patch(route('goals.update-status', $goal), ['status' => 'paused'])
            ->assertForbidden();
    }

    // =========================================================================
    // INDEX
    // =========================================================================

    public function test_user_can_view_goals_index()
    {
        $this->actingAs($this->user)
            ->get(route('goals.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Goals/Index')
                ->has('items')
                ->has('categories')
            );
    }

    // =========================================================================
    // SHOW
    // =========================================================================

    public function test_user_can_view_their_goal()
    {
        $goal = Goal::factory()->create([
            'user_id' => $this->user->id,
            'current_value' => 0,
        ]);

        $this->actingAs($this->user)
            ->get(route('goals.show', $goal))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Goals/Show')
                ->has('goal')
                ->has('chartEntries')
                ->where('goal.id', $goal->id)
            );
    }

    // =========================================================================
    // CREATE / STORE
    // =========================================================================

    public function test_user_can_create_a_goal()
    {
        $this->actingAs($this->user)
            ->post(route('goals.store'), $this->validGoalData())
            ->assertRedirect(route('goals.index'))
            ->assertInertiaFlash('toast.type', 'success')
            ->assertInertiaFlash('toast.message', 'Goal created.');

        $this->assertDatabaseHas('goals', [
            'title' => 'Test Goal',
            'user_id' => $this->user->id,
        ]);
    }

    public function test_create_page_passes_selected_category_when_owned_by_user()
    {
        $category = Category::factory()->create(['user_id' => $this->user->id]);

        $this->actingAs($this->user)
            ->get(route('goals.create', ['category' => $category->id]))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Goals/Create')
                ->where('selectedCategory', (string) $category->id)
            );
    }

    public function test_create_page_does_not_pass_category_owned_by_another_user()
    {
        $otherCategory = Category::factory()->create(['user_id' => $this->otherUser->id]);

        $this->actingAs($this->user)
            ->get(route('goals.create', ['category' => $otherCategory->id]))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Goals/Create')
                ->where('selectedCategory', null)
            );
    }

    public function test_create_page_has_null_selected_category_without_category_param()
    {
        $this->actingAs($this->user)
            ->get(route('goals.create'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Goals/Create')
                ->where('selectedCategory', null)
            );

        $this->actingAs($this->user)
            ->get(route('goals.create', ['category' => 'not-a-number']))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Goals/Create')
                ->where('selectedCategory', null)
            );
    }

    public function test_title_is_required()
    {
        $this->actingAs($this->user)
            ->post(route('goals.store'), $this->validGoalData(['title' => '']))
            ->assertSessionHasErrors('title');
    }

    public function test_type_must_be_valid()
    {
        $this->actingAs($this->user)
            ->post(route('goals.store'), $this->validGoalData(['type' => 'invalid']))
            ->assertSessionHasErrors('type');
    }

    public function test_quantifiable_goal_requires_target_value()
    {
        $this->actingAs($this->user)
            ->post(route('goals.store'), $this->validGoalData([
                'type' => 'quantifiable',
                'target_value' => null,
            ]))
            ->assertSessionHasErrors('target_value');
    }

    public function test_deadline_must_be_a_valid_date()
    {
        $this->actingAs($this->user)
            ->post(route('goals.store'), $this->validGoalData(['deadline' => 'not-a-date']))
            ->assertSessionHasErrors('deadline');
    }

    public function test_polarity_must_be_valid()
    {
        $this->actingAs($this->user)
            ->post(route('goals.store'), $this->validGoalData([
                'type' => 'recurring',
                'polarity' => 'not-valid',
            ]))
            ->assertSessionHasErrors('polarity');
    }

    // =========================================================================
    // EDIT / UPDATE
    // =========================================================================

    public function test_user_can_update_their_goal()
    {
        $goal = Goal::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'simple',
            'current_value' => 0,
            'status' => 'in_progress',
        ]);

        $this->actingAs($this->user)
            ->put(route('goals.update', $goal), $this->validGoalData([
                'title' => 'Updated Title',
                'status' => $goal->status,
            ]))
            ->assertRedirect()
            ->assertInertiaFlash('toast.type', 'success')
            ->assertInertiaFlash('toast.message', 'Goal updated.');

        $this->assertEquals('Updated Title', $goal->fresh()->title);
    }

    public function test_update_validates_title()
    {
        $goal = Goal::factory()->create([
            'user_id' => $this->user->id,
            'current_value' => 0,
        ]);

        $this->actingAs($this->user)
            ->put(route('goals.update', $goal), $this->validGoalData(['title' => '']))
            ->assertSessionHasErrors('title');
    }

    public function test_update_persists_polarity()
    {
        $goal = Goal::factory()->create([
            'user_id' => $this->user->id,
            'current_value' => 0,
        ]);

        $this->actingAs($this->user)
            ->put(route('goals.update', $goal), $this->validGoalData([
                'polarity' => 'positive',
            ]))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('goals', [
            'id' => $goal->id,
            'polarity' => 'positive',
        ]);
    }

    // =========================================================================
    // DESTROY
    // =========================================================================

    public function test_user_can_delete_their_goal()
    {
        $goal = Goal::factory()->create([
            'user_id' => $this->user->id,
            'current_value' => 0,
        ]);

        $this->actingAs($this->user)
            ->delete(route('goals.destroy', $goal))
            ->assertRedirect()
            ->assertInertiaFlash('toast.type', 'success')
            ->assertInertiaFlash('toast.message', 'Goal deleted.');

        $this->assertModelMissing($goal);
    }

    // =========================================================================
    // UPDATE STATUS
    // =========================================================================

    public function test_user_can_pause_an_in_progress_goal()
    {
        $goal = Goal::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'simple',
            'status' => 'in_progress',
            'current_value' => 0,
        ]);

        $this->actingAs($this->user)
            ->patch(route('goals.update-status', $goal), ['status' => 'paused'])
            ->assertRedirect()
            ->assertInertiaFlash('toast.type', 'success')
            ->assertInertiaFlash('toast.message', 'Goal status updated.');

        $this->assertEquals('paused', $goal->fresh()->status);
    }

    public function test_user_can_resume_a_paused_goal()
    {
        $goal = Goal::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'simple',
            'status' => 'paused',
            'current_value' => 0,
        ]);

        $this->actingAs($this->user)
            ->patch(route('goals.update-status', $goal), ['status' => 'in_progress'])
            ->assertRedirect();

        $this->assertEquals('in_progress', $goal->fresh()->status);
    }

    public function test_invalid_status_value_is_rejected()
    {
        $goal = Goal::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'in_progress',
            'current_value' => 0,
        ]);

        $this->actingAs($this->user)
            ->patch(route('goals.update-status', $goal), ['status' => 'invalid_status'])
            ->assertSessionHasErrors('status');
    }

    // =========================================================================
    // COMPLETE
    // =========================================================================

    public function test_user_can_complete_a_goal()
    {
        $goal = Goal::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'simple',
            'status' => 'in_progress',
            'current_value' => 0,
        ]);

        $this->actingAs($this->user)
            ->patch(route('goals.complete', $goal))
            ->assertRedirect()
            ->assertInertiaFlash('toast.type', 'success')
            ->assertInertiaFlash('toast.message', 'Goal completed.');

        $goal->refresh();
        $this->assertEquals('completed', $goal->status);
        $this->assertNotNull($goal->completed_at);
    }

    public function test_complete_flashes_an_undo_action_carrying_the_previous_status()
    {
        $goal = Goal::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'simple',
            'status' => 'in_progress',
            'current_value' => 0,
        ]);

        $this->actingAs($this->user)
            ->patch(route('goals.complete', $goal))
            ->assertInertiaFlash('toast.action.label', 'Undo')
            ->assertInertiaFlash('toast.action.method', 'patch')
            ->assertInertiaFlash('toast.action.data.status', 'in_progress');
    }

    public function test_user_cannot_complete_another_users_goal()
    {
        $goal = Goal::factory()->create(['user_id' => $this->otherUser->id, 'current_value' => 0]);

        $this->actingAs($this->user)
            ->patch(route('goals.complete', $goal))
            ->assertForbidden();
    }

    // =========================================================================
    // UNCOMPLETE
    // =========================================================================

    public function test_user_can_uncomplete_a_goal()
    {
        $goal = Goal::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'simple',
            'status' => 'completed',
            'completed_at' => now(),
            'current_value' => 0,
        ]);

        $this->actingAs($this->user)
            ->patch(route('goals.uncomplete', $goal), ['status' => 'in_progress'])
            ->assertRedirect()
            ->assertInertiaFlash('toast.type', 'success')
            ->assertInertiaFlash('toast.message', 'Goal completion reverted.');

        $goal->refresh();
        $this->assertEquals('in_progress', $goal->status);
        $this->assertNull($goal->completed_at);
    }

    public function test_uncomplete_restores_a_not_started_goal()
    {
        // Regression: a goal completed while still "not_started" must be restorable.
        $goal = Goal::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'simple',
            'status' => 'completed',
            'completed_at' => now(),
            'current_value' => 0,
        ]);

        $this->actingAs($this->user)
            ->patch(route('goals.uncomplete', $goal), ['status' => 'not_started'])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $goal->refresh();
        $this->assertEquals('not_started', $goal->status);
        $this->assertNull($goal->completed_at);
    }

    public function test_uncomplete_rejects_the_completed_status()
    {
        $goal = Goal::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'completed',
            'completed_at' => now(),
            'current_value' => 0,
        ]);

        $this->actingAs($this->user)
            ->patch(route('goals.uncomplete', $goal), ['status' => 'completed'])
            ->assertSessionHasErrors('status');

        $this->assertEquals('completed', $goal->fresh()->status);
    }

    public function test_uncomplete_rejects_an_invalid_status()
    {
        $goal = Goal::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'completed',
            'completed_at' => now(),
            'current_value' => 0,
        ]);

        $this->actingAs($this->user)
            ->patch(route('goals.uncomplete', $goal), ['status' => 'banana'])
            ->assertSessionHasErrors('status');
    }

    public function test_uncomplete_requires_a_status()
    {
        $goal = Goal::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'completed',
            'completed_at' => now(),
            'current_value' => 0,
        ]);

        $this->actingAs($this->user)
            ->patch(route('goals.uncomplete', $goal))
            ->assertSessionHasErrors('status');
    }

    public function test_user_cannot_uncomplete_another_users_goal()
    {
        $goal = Goal::factory()->create([
            'user_id' => $this->otherUser->id,
            'status' => 'completed',
            'completed_at' => now(),
            'current_value' => 0,
        ]);

        $this->actingAs($this->user)
            ->patch(route('goals.uncomplete', $goal), ['status' => 'in_progress'])
            ->assertForbidden();
    }

    // =========================================================================
    // LAZY AUTO-COMPLETION (NEGATIVE GOALS)
    // =========================================================================

    public function test_negative_goal_past_deadline_with_intact_streak_completes_on_view()
    {
        Carbon::setTestNow('2026-07-06 10:00:00');

        $goal = Goal::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'recurring',
            'polarity' => 'negative',
            'recurrence' => 'daily',
            'start_date' => '2026-05-27',
            'deadline' => '2026-07-05',
            'status' => 'in_progress',
        ]);

        $this->actingAs($this->user)
            ->get(route('goals.show', $goal))
            ->assertInertia(fn (Assert $page) => $page
                ->hasFlash('toast.message', 'Goal completed.')
            );

        $goal->refresh();

        $this->assertEquals('completed', $goal->status);
        $this->assertNotNull($goal->completed_at);

        Carbon::setTestNow();
    }

    public function test_negative_goal_with_a_relapse_in_window_stays_active_and_overdue()
    {
        Carbon::setTestNow('2026-07-06 10:00:00');

        $goal = Goal::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'recurring',
            'polarity' => 'negative',
            'recurrence' => 'daily',
            'start_date' => '2026-05-27',
            'deadline' => '2026-07-05',
            'status' => 'in_progress',
        ]);

        GoalEntry::factory()->create([
            'goal_id' => $goal->id,
            'entry_date' => '2026-06-15',
        ]);

        $this->actingAs($this->user)
            ->get(route('goals.show', $goal))
            ->assertInertia(fn (Assert $page) => $page
                ->where('goal.is_overdue', true)
                ->where('goal.status', 'in_progress')
            );

        $this->assertEquals('in_progress', $goal->fresh()->status);

        Carbon::setTestNow();
    }

    public function test_viewing_an_already_completed_eligible_goal_does_not_refire()
    {
        Carbon::setTestNow('2026-07-06 10:00:00');

        $completedAt = now();

        $goal = Goal::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'recurring',
            'polarity' => 'negative',
            'recurrence' => 'daily',
            'start_date' => '2026-05-27',
            'deadline' => '2026-07-05',
            'status' => 'completed',
            'completed_at' => $completedAt,
        ]);

        $this->actingAs($this->user)->get(route('goals.show', $goal));
        $this->actingAs($this->user)->get(route('goals.show', $goal));

        $goal->refresh();

        $this->assertEquals('completed', $goal->status);
        $this->assertEquals(
            $completedAt->timestamp,
            $goal->completed_at?->timestamp,
        );

        Carbon::setTestNow();
    }
}
