<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Goal;
use App\Models\Milestone;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\AssertsUserData;
use Tests\TestCase;

class MilestoneControllerTest extends TestCase
{
    use AssertsUserData;
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
            'direction' => 'ascending',
            'initial_value' => 0,
            'current_value' => 0,
            'target_value' => 100,
            'status' => 'in_progress',
        ]);
    }

    // =========================================================================
    // AUTHORIZATION
    // =========================================================================

    public function test_guest_is_redirected_to_login()
    {
        $milestone = Milestone::factory()->create(['goal_id' => $this->goal->id]);

        $this->post(route('milestones.store', $this->goal), ['title' => 'M'])
            ->assertRedirect(route('login'));
        $this->put(route('milestones.update', [$this->goal, $milestone]), ['title' => 'M'])
            ->assertRedirect(route('login'));
        $this->delete(route('milestones.destroy', [$this->goal, $milestone]))
            ->assertRedirect(route('login'));
        $this->patch(route('milestones.complete', [$this->goal, $milestone]))
            ->assertRedirect(route('login'));
        $this->patch(route('milestones.uncomplete', [$this->goal, $milestone]))
            ->assertRedirect(route('login'));
    }

    public function test_user_cannot_create_milestone_on_other_users_goal()
    {
        $otherGoal = Goal::factory()->create(['user_id' => $this->otherUser->id]);

        $this->actingAs($this->user)
            ->post(route('milestones.store', $otherGoal), ['title' => 'Sneaky'])
            ->assertForbidden();

        $this->assertUserDataMissing(Milestone::class, ['title' => 'Sneaky']);
    }

    public function test_user_cannot_update_other_users_milestone()
    {
        $otherGoal = Goal::factory()->create(['user_id' => $this->otherUser->id]);
        $milestone = Milestone::factory()->create([
            'goal_id' => $otherGoal->id,
            'title' => 'Original',
        ]);

        $this->actingAs($this->user)
            ->put(route('milestones.update', [$otherGoal, $milestone]), ['title' => 'Hacked'])
            ->assertForbidden();

        $this->assertEquals('Original', $milestone->fresh()->title);
    }

    public function test_user_cannot_delete_other_users_milestone()
    {
        $otherGoal = Goal::factory()->create(['user_id' => $this->otherUser->id]);
        $milestone = Milestone::factory()->create(['goal_id' => $otherGoal->id]);

        $this->actingAs($this->user)
            ->delete(route('milestones.destroy', [$otherGoal, $milestone]))
            ->assertForbidden();

        $this->assertModelExists($milestone);
    }

    public function test_user_cannot_complete_other_users_milestone()
    {
        $otherGoal = Goal::factory()->create(['user_id' => $this->otherUser->id]);
        $milestone = Milestone::factory()->create([
            'goal_id' => $otherGoal->id,
            'completed_at' => null,
        ]);

        $this->actingAs($this->user)
            ->patch(route('milestones.complete', [$otherGoal, $milestone]))
            ->assertForbidden();

        $this->assertNull($milestone->fresh()->completed_at);
    }

    public function test_user_cannot_uncomplete_other_users_milestone()
    {
        $otherGoal = Goal::factory()->create(['user_id' => $this->otherUser->id]);
        $milestone = Milestone::factory()->create([
            'goal_id' => $otherGoal->id,
            'completed_at' => now(),
        ]);

        $this->actingAs($this->user)
            ->patch(route('milestones.uncomplete', [$otherGoal, $milestone]))
            ->assertForbidden();

        $this->assertNotNull($milestone->fresh()->completed_at);
    }

    public function test_milestone_must_belong_to_the_goal_in_the_route()
    {
        // The user owns both goals, but the milestone belongs to $this->goal
        // while the route references a different goal — the controller guard
        // must reject the mismatch.
        $anotherOwnGoal = Goal::factory()->create(['user_id' => $this->user->id]);
        $milestone = Milestone::factory()->create([
            'goal_id' => $this->goal->id,
            'title' => 'Original',
        ]);

        $this->actingAs($this->user)
            ->put(route('milestones.update', [$anotherOwnGoal, $milestone]), ['title' => 'Mismatched'])
            ->assertForbidden();

        $this->assertEquals('Original', $milestone->fresh()->title);
    }

    // =========================================================================
    // STORE
    // =========================================================================

    public function test_user_can_create_a_milestone_on_their_goal()
    {
        $this->actingAs($this->user)
            ->post(route('milestones.store', $this->goal), [
                'title' => 'Reach 25%',
                'target_value' => 25,
            ])
            ->assertRedirect()
            ->assertInertiaFlash('toast.type', 'success')
            ->assertInertiaFlash('toast.message', 'Milestone added.');

        $this->assertUserDataHas(Milestone::class, [
            'goal_id' => $this->goal->id,
            'title' => 'Reach 25%',
            'target_value' => 25,
        ]);
    }

    public function test_first_milestone_is_assigned_order_one()
    {
        $this->actingAs($this->user)
            ->post(route('milestones.store', $this->goal), ['title' => 'First']);

        $this->assertUserDataHas(Milestone::class, [
            'goal_id' => $this->goal->id,
            'title' => 'First',
            'order' => 1,
        ]);
    }

    public function test_new_milestone_order_is_max_plus_one()
    {
        $this->goal->update(['type' => 'multi_step']);
        Milestone::factory()->create(['goal_id' => $this->goal->id, 'order' => 5]);

        $this->actingAs($this->user)
            ->post(route('milestones.store', $this->goal), ['title' => 'Next']);

        $this->assertUserDataHas(Milestone::class, [
            'goal_id' => $this->goal->id,
            'title' => 'Next',
            'order' => 6,
        ]);
    }

    public function test_client_supplied_order_is_ignored_on_create()
    {
        $this->goal->update(['type' => 'multi_step']);
        Milestone::factory()->create(['goal_id' => $this->goal->id, 'order' => 3]);

        $this->actingAs($this->user)
            ->post(route('milestones.store', $this->goal), [
                'title' => 'Tampered',
                'order' => 99,
            ]);

        $this->assertUserDataHas(Milestone::class, [
            'goal_id' => $this->goal->id,
            'title' => 'Tampered',
            'order' => 4,
        ]);
    }

    public function test_milestone_title_is_required()
    {
        $this->actingAs($this->user)
            ->post(route('milestones.store', $this->goal), ['title' => null])
            ->assertSessionHasErrors('title');
    }

    public function test_milestone_target_value_must_be_numeric()
    {
        $this->actingAs($this->user)
            ->post(route('milestones.store', $this->goal), [
                'title' => 'Bad target',
                'target_value' => 'not-a-number',
            ])
            ->assertSessionHasErrors('target_value');
    }

    public function test_milestone_points_reward_must_be_numeric()
    {
        $this->actingAs($this->user)
            ->post(route('milestones.store', $this->goal), [
                'title' => 'Bad reward',
                'points_reward' => 'not-a-number',
            ])
            ->assertSessionHasErrors('points_reward');
    }

    public function test_milestone_completed_at_must_be_a_date()
    {
        $this->actingAs($this->user)
            ->post(route('milestones.store', $this->goal), [
                'title' => 'Bad completion date',
                'completed_at' => 'not-a-date',
            ])
            ->assertSessionHasErrors('completed_at');
    }

    // =========================================================================
    // UPDATE
    // =========================================================================

    public function test_user_can_update_their_milestone()
    {
        $milestone = Milestone::factory()->create([
            'goal_id' => $this->goal->id,
            'title' => 'Old title',
        ]);

        $this->actingAs($this->user)
            ->put(route('milestones.update', [$this->goal, $milestone]), [
                'title' => 'New title',
            ])
            ->assertRedirect()
            ->assertInertiaFlash('toast.type', 'success')
            ->assertInertiaFlash('toast.message', 'Milestone updated.');

        $this->assertEquals('New title', $milestone->fresh()->title);
    }

    public function test_updating_milestone_requires_a_title()
    {
        $milestone = Milestone::factory()->create(['goal_id' => $this->goal->id]);

        $this->actingAs($this->user)
            ->put(route('milestones.update', [$this->goal, $milestone]), ['title' => null])
            ->assertSessionHasErrors('title');
    }

    // =========================================================================
    // DESTROY
    // =========================================================================

    public function test_user_can_delete_their_milestone()
    {
        $milestone = Milestone::factory()->create(['goal_id' => $this->goal->id]);

        $this->actingAs($this->user)
            ->delete(route('milestones.destroy', [$this->goal, $milestone]))
            ->assertRedirect()
            ->assertInertiaFlash('toast.type', 'success')
            ->assertInertiaFlash('toast.message', 'Milestone deleted.');

        $this->assertModelMissing($milestone);
    }

    // =========================================================================
    // COMPLETE / UNCOMPLETE
    // =========================================================================

    public function test_user_can_complete_their_milestone()
    {
        $milestone = Milestone::factory()->create([
            'goal_id' => $this->goal->id,
            'completed_at' => null,
        ]);

        $this->actingAs($this->user)
            ->patch(route('milestones.complete', [$this->goal, $milestone]))
            ->assertRedirect()
            ->assertInertiaFlash('toast.type', 'success')
            ->assertInertiaFlash('toast.message', 'Milestone completed.');

        $this->assertNotNull($milestone->fresh()->completed_at);
    }

    public function test_user_can_uncomplete_their_milestone()
    {
        $milestone = Milestone::factory()->create([
            'goal_id' => $this->goal->id,
            'completed_at' => now(),
        ]);

        $this->actingAs($this->user)
            ->patch(route('milestones.uncomplete', [$this->goal, $milestone]))
            ->assertRedirect()
            ->assertInertiaFlash('toast.type', 'success')
            ->assertInertiaFlash('toast.message', 'Milestone marked incomplete.');

        $this->assertNull($milestone->fresh()->completed_at);
    }

    public function test_completing_and_uncompleting_a_step_shows_step_toasts()
    {
        $stepGoal = Goal::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'multi_step',
        ]);
        $step = Milestone::factory()->create([
            'goal_id' => $stepGoal->id,
            'target_value' => null,
        ]);

        $this->actingAs($this->user)
            ->patch(route('milestones.complete', [$stepGoal, $step]))
            ->assertRedirect()
            ->assertInertiaFlash('toast.message', 'Step completed.');

        $this->assertNotNull($step->fresh()->completed_at);

        $this->actingAs($this->user)
            ->patch(route('milestones.uncomplete', [$stepGoal, $step]))
            ->assertRedirect()
            ->assertInertiaFlash('toast.message', 'Step marked incomplete.');

        $this->assertNull($step->fresh()->completed_at);
    }

    private function multiStepGoalWithSteps(int $count): array
    {
        $this->goal->update(['type' => 'multi_step']);

        return collect(range(1, $count))
            ->map(fn (int $order) => Milestone::factory()->create([
                'goal_id' => $this->goal->id,
                'title' => "Step {$order}",
                'target_value' => null,
                'order' => $order,
            ]))
            ->all();
    }

    private function stepTitlesInOrder(): array
    {
        return $this->goal->milestones()->get()->pluck('title')->all();
    }

    public function test_user_can_reorder_the_steps_of_their_goal()
    {
        [$first, $second, $third] = $this->multiStepGoalWithSteps(3);

        $this->actingAs($this->user)
            ->patch(route('milestones.reorder', $this->goal), [
                'milestones' => [$third->id, $first->id, $second->id],
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertSame(['Step 3', 'Step 1', 'Step 2'], $this->stepTitlesInOrder());
    }

    public function test_user_cannot_reorder_steps_of_other_users_goal()
    {
        $foreignGoal = Goal::factory()->create(['user_id' => $this->otherUser->id, 'type' => 'multi_step']);
        $step = Milestone::factory()->create(['goal_id' => $foreignGoal->id]);

        $this->actingAs($this->user)
            ->patch(route('milestones.reorder', $foreignGoal), ['milestones' => [$step->id]])
            ->assertForbidden();
    }

    public function test_reorder_must_list_every_step_of_the_goal_exactly_once()
    {
        [$first, $second] = $this->multiStepGoalWithSteps(2);

        $this->actingAs($this->user)
            ->patch(route('milestones.reorder', $this->goal), ['milestones' => [$second->id]])
            ->assertSessionHasErrors('milestones');

        $this->actingAs($this->user)
            ->patch(route('milestones.reorder', $this->goal), ['milestones' => [$second->id, $second->id]])
            ->assertSessionHasErrors('milestones.0');

        $this->assertSame(['Step 1', 'Step 2'], $this->stepTitlesInOrder());
    }

    public function test_reorder_rejects_a_milestone_from_another_goal()
    {
        [$first] = $this->multiStepGoalWithSteps(2);
        $foreignStep = Milestone::factory()->create();

        $this->actingAs($this->user)
            ->patch(route('milestones.reorder', $this->goal), ['milestones' => [$foreignStep->id, $first->id]])
            ->assertSessionHasErrors('milestones.0');
    }

    public function test_quantifiable_milestones_cannot_be_reordered()
    {
        $low = Milestone::factory()->create(['goal_id' => $this->goal->id, 'target_value' => 10, 'order' => 1]);
        $high = Milestone::factory()->create(['goal_id' => $this->goal->id, 'target_value' => 50, 'order' => 2]);

        $this->actingAs($this->user)
            ->patch(route('milestones.reorder', $this->goal), ['milestones' => [$high->id, $low->id]])
            ->assertSessionHasErrors('milestones');

        $this->assertSame(1, $low->fresh()->order);
    }

    public function test_a_step_can_be_inserted_at_a_position()
    {
        $this->multiStepGoalWithSteps(3);

        $this->actingAs($this->user)
            ->post(route('milestones.store', $this->goal), ['title' => 'Forgotten', 'position' => 2])
            ->assertSessionHasNoErrors();

        $this->assertSame(['Step 1', 'Forgotten', 'Step 2', 'Step 3'], $this->stepTitlesInOrder());
        $this->assertSame([1, 2, 3, 4], $this->goal->milestones()->pluck('order')->all());
    }

    public function test_a_position_past_the_end_appends_the_step()
    {
        $this->multiStepGoalWithSteps(2);

        $this->actingAs($this->user)
            ->post(route('milestones.store', $this->goal), ['title' => 'Last', 'position' => 99])
            ->assertSessionHasNoErrors();

        $this->assertSame(['Step 1', 'Step 2', 'Last'], $this->stepTitlesInOrder());
    }

    public function test_a_position_must_be_a_positive_integer()
    {
        $this->multiStepGoalWithSteps(1);

        $this->actingAs($this->user)
            ->post(route('milestones.store', $this->goal), ['title' => 'Nowhere', 'position' => 0])
            ->assertSessionHasErrors('position');
    }

    public function test_milestone_deadline_must_fall_within_the_goal_dates()
    {
        $this->goal->update(['start_date' => '2026-10-01', 'deadline' => '2026-10-31']);

        $this->actingAs($this->user)
            ->post(route('milestones.store', $this->goal), ['title' => 'Early', 'deadline' => '2026-09-30'])
            ->assertSessionHasErrors('deadline');

        $this->actingAs($this->user)
            ->post(route('milestones.store', $this->goal), ['title' => 'Late', 'deadline' => '2026-11-01'])
            ->assertSessionHasErrors('deadline');

        $this->actingAs($this->user)
            ->post(route('milestones.store', $this->goal), ['title' => 'On time', 'deadline' => '2026-10-31'])
            ->assertSessionHasNoErrors();

        $this->assertUserDataHas(Milestone::class, ['title' => 'On time']);
    }

    public function test_milestone_deadline_can_be_updated_and_cleared()
    {
        $this->goal->update(['start_date' => '2026-10-01', 'deadline' => '2027-01-31']);
        $milestone = Milestone::factory()->create(['goal_id' => $this->goal->id]);

        $this->actingAs($this->user)
            ->put(route('milestones.update', [$this->goal, $milestone]), ['title' => 'Dated', 'deadline' => '2026-12-01'])
            ->assertSessionHasNoErrors();

        $this->assertSame('2026-12-01', $milestone->fresh()->deadline->toDateString());

        $this->actingAs($this->user)
            ->put(route('milestones.update', [$this->goal, $milestone]), ['title' => 'Dated', 'deadline' => null])
            ->assertSessionHasNoErrors();

        $this->assertNull($milestone->fresh()->deadline);
    }
}
