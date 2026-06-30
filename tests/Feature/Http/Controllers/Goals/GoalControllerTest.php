<?php

namespace Tests\Feature\Http\Controllers\Goals;

use App\Models\Goal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
