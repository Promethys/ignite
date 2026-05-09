<?php

namespace Tests\Feature\Http\Controllers\Goals;

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
            'type' => 'multi_step',
            'completed_at' => null,
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
        $milestone = Milestone::factory()->create([
            'goal_id' => $this->goal->id,
        ]);

        $this->actingAsGuest()
            ->post(route('milestones.store', [$this->goal]), [
                'title' => 'Milestone title',
            ])
            ->assertRedirect(route('login'));
        $this->actingAsGuest()
            ->put(route('milestones.update', [$this->goal, $milestone]), [
                'title' => 'Milestone title',
            ])
            ->assertRedirect(route('login'));
        $this->actingAsGuest()
            ->delete(route('milestones.destroy', [$this->goal, $milestone]))
            ->assertRedirect(route('login'));
        $this->actingAsGuest()
            ->patch(route('milestones.complete', [$this->goal, $milestone]))
            ->assertRedirect(route('login'));
    }

    // =========================================================================
    // STORE
    // =========================================================================

    public function test_user_can_add_milestone_to_their_goal()
    {
        $this->actingAs($this->user)
            ->post(route('milestones.store', $this->goal), [
                'title' => 'Milestone 1',
                'target_value' => 0,
            ])
            ->assertRedirectBack();

        $this->assertDatabaseHas('milestones', [
            'goal_id' => $this->goal->id,
            'title' => 'Milestone 1',
            'target_value' => 0,
        ]);
    }

    public function test_milestone_title_is_required()
    {
        $this->actingAs($this->user)
            ->post(route('milestones.store', $this->goal), [
                'title' => null,
            ])
            ->assertSessionHasErrors('title');
    }

    public function test_milestone_target_value_is_numeric()
    {
        $this->actingAs($this->user)
            ->post(route('milestones.store', $this->goal), [
                'title' => 'Milestone 1',
                'description' => 'Description',
                'target_value' => 'Invalid value',
            ])
            ->assertSessionHasErrors('target_value');

        $this->actingAs($this->user)
            ->post(route('milestones.store', $this->goal), [
                'title' => 'Milestone 1',
                'description' => 'Description',
                'target_value' => 50,
            ])
            ->assertRedirectBack();
    }

    public function test_milestone_points_reward_is_numeric()
    {
        $this->actingAs($this->user)
            ->post(route('milestones.store', $this->goal), [
                'title' => 'Milestone 1',
                'description' => 'Description',
                'points_reward' => 'Invalid value',
            ])
            ->assertSessionHasErrors('points_reward');

        $this->actingAs($this->user)
            ->post(route('milestones.store', $this->goal), [
                'title' => 'Milestone 1',
                'description' => 'Description',
                'points_reward' => 50,
            ])
            ->assertRedirectBack();
    }

    public function test_milestone_completed_at_is_date()
    {
        $this->actingAs($this->user)
            ->post(route('milestones.store', $this->goal), [
                'title' => 'Milestone 1',
                'description' => 'Description',
                'completed_at' => 'Invalid value',
            ])
            ->assertSessionHasErrors('completed_at');

        $this->actingAs($this->user)
            ->post(route('milestones.store', $this->goal), [
                'title' => 'Milestone 1',
                'description' => 'Description',
                'completed_at' => now(),
            ])
            ->assertRedirectBack();
    }

    public function test_user_cannot_add_milestone_to_other_users_goal()
    {
        $otherGoal = Goal::factory()->create([
            'user_id' => $this->otherUser->id,
            'current_value' => 0,
            'type' => 'multi_step',
        ]);

        $this->actingAs($this->user)
            ->post(route('milestones.store', [
                'goal' => $otherGoal,
            ]), [
                'title' => 'Milestone 1',
            ])
            ->assertForbidden();
    }

    // =========================================================================
    // UPDATE
    // =========================================================================

    public function test_user_can_update_their_milestone()
    {
        $milestoneInitialData = [
            'goal_id' => $this->goal->id,
            'title' => 'Initial title',
            'description' => 'Initial description',
            'target_value' => 10,
            'completed_at' => null,
            'points_reward' => 10,
        ];

        $milestoneEditedData = [
            'title' => 'Edited title',
            'description' => 'Edited description',
            'target_value' => 5,
            'completed_at' => now(),
            'points_reward' => 5,
        ];

        $milestone = Milestone::factory()->create($milestoneInitialData);

        $this->assertDatabaseHas('milestones', $milestoneInitialData);

        $this->actingAs($this->user)
            ->put(route('milestones.update', [$this->goal, $milestone]), $milestoneEditedData)
            ->assertRedirect();

        $this->assertDatabaseHas('milestones', [
            ...$milestoneEditedData,
            'goal_id' => $this->goal->id,
        ]);
    }

    public function test_user_cannot_update_other_users_milestone()
    {
        $otherGoal = Goal::factory()->create([
            'user_id' => $this->otherUser->id,
            'current_value' => 0,
            'type' => 'multi_step',
        ]);

        $otherMilestone = Milestone::factory()->create([
            'goal_id' => $otherGoal->id,
        ]);

        $this->actingAs($this->user)
            ->put(route('milestones.update', [
                'goal' => $otherGoal,
                'milestone' => $otherMilestone,
            ]), [
                'title' => 'Milestone 1',
            ])
            ->assertForbidden();
    }

    public function test_user_cannot_update_their_milestone_of_the_wrong_goal()
    {
        $otherGoal = Goal::factory()->create([
            'user_id' => $this->user->id,
            'current_value' => 10,
        ]);
        $milestone = Milestone::factory()->create([
            'goal_id' => $this->goal->id,
            'target_value' => 10,
        ]);

        $this->actingAs($this->user)
            ->put(route('milestones.update', [$otherGoal, $milestone]), [
                'title' => 'Edited title',
            ])
            ->assertForbidden();
    }

    // =========================================================================
    // DELETE
    // =========================================================================

    public function test_user_can_delete_their_milestone()
    {
        $milestone = Milestone::factory()->create([
            'goal_id' => $this->goal->id,
            'target_value' => 10,
        ]);

        $this->actingAs($this->user)
            ->delete(route('milestones.destroy', [$this->goal, $milestone]))
            ->assertRedirect();

        $this->assertModelMissing($milestone);
    }

    public function test_user_cannot_delete_other_users_milestone()
    {
        $otherGoal = Goal::factory()->create([
            'user_id' => $this->otherUser->id,
            'current_value' => 10,
        ]);
        $milestone = Milestone::factory()->create([
            'goal_id' => $otherGoal->id,
            'target_value' => 10,
        ]);

        $this->actingAs($this->user)
            ->delete(route('milestones.destroy', [$otherGoal, $milestone]))
            ->assertForbidden();

        $this->assertModelExists($milestone);
    }
}
