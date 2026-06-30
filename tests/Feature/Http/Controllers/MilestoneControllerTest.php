<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Goal;
use App\Models\Milestone;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MilestoneControllerTest extends TestCase
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
    }

    public function test_user_cannot_create_milestone_on_other_users_goal()
    {
        $otherGoal = Goal::factory()->create(['user_id' => $this->otherUser->id]);

        $this->actingAs($this->user)
            ->post(route('milestones.store', $otherGoal), ['title' => 'Sneaky'])
            ->assertForbidden();

        $this->assertDatabaseMissing('milestones', ['title' => 'Sneaky']);
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
            ->assertRedirect();

        $this->assertDatabaseHas('milestones', [
            'goal_id' => $this->goal->id,
            'title' => 'Reach 25%',
            'target_value' => 25,
        ]);
    }

    public function test_first_milestone_is_assigned_order_one()
    {
        $this->actingAs($this->user)
            ->post(route('milestones.store', $this->goal), ['title' => 'First']);

        $this->assertDatabaseHas('milestones', [
            'goal_id' => $this->goal->id,
            'title' => 'First',
            'order' => 1,
        ]);
    }

    public function test_new_milestone_order_is_max_plus_one()
    {
        Milestone::factory()->create(['goal_id' => $this->goal->id, 'order' => 5]);

        $this->actingAs($this->user)
            ->post(route('milestones.store', $this->goal), ['title' => 'Next']);

        $this->assertDatabaseHas('milestones', [
            'goal_id' => $this->goal->id,
            'title' => 'Next',
            'order' => 6,
        ]);
    }

    public function test_client_supplied_order_is_ignored_on_create()
    {
        Milestone::factory()->create(['goal_id' => $this->goal->id, 'order' => 3]);

        $this->actingAs($this->user)
            ->post(route('milestones.store', $this->goal), [
                'title' => 'Tampered',
                'order' => 99,
            ]);

        $this->assertDatabaseHas('milestones', [
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
            ->assertRedirect();

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
            ->assertRedirect();

        $this->assertModelMissing($milestone);
    }

    // =========================================================================
    // COMPLETE
    // =========================================================================

    public function test_user_can_complete_their_milestone()
    {
        $milestone = Milestone::factory()->create([
            'goal_id' => $this->goal->id,
            'completed_at' => null,
        ]);

        $this->actingAs($this->user)
            ->patch(route('milestones.complete', [$this->goal, $milestone]))
            ->assertRedirect();

        $this->assertNotNull($milestone->fresh()->completed_at);
    }
}
