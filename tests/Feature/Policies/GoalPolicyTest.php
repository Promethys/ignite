<?php

namespace Tests\Feature\Policies;

use App\Models\Goal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\WithAdminRole;
use Tests\TestCase;

class GoalPolicyTest extends TestCase
{
    use RefreshDatabase;
    use WithAdminRole;

    private User $owner;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpAdminRole();

        $this->owner = User::factory()->create();
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
    }

    private function ownedGoal(): Goal
    {
        return Goal::factory()->create([
            'user_id' => $this->owner->id,
            'current_value' => 0,
        ]);
    }

    private function enterAdminPanel(): void
    {
        filament()->setCurrentPanel('admin');
    }

    // =========================================================================
    // OWNER — full access to their own goal
    // =========================================================================

    public function test_owner_can_view_update_and_delete_their_own_goal()
    {
        $goal = $this->ownedGoal();

        $this->assertTrue($this->owner->can('view', $goal));
        $this->assertTrue($this->owner->can('update', $goal));
        $this->assertTrue($this->owner->can('delete', $goal));
    }

    // =========================================================================
    // PLAIN USER — no access to someone else's goal
    // =========================================================================

    public function test_a_plain_user_cannot_view_update_or_delete_another_users_goal()
    {
        $goal = $this->ownedGoal();
        $stranger = User::factory()->create();

        $this->assertFalse($stranger->can('view', $goal));
        $this->assertFalse($stranger->can('update', $goal));
        $this->assertFalse($stranger->can('delete', $goal));
    }

    // =========================================================================
    // ADMIN — no access to another user's goal, in the app or the panel
    // =========================================================================

    public function test_admin_cannot_view_update_or_delete_another_users_goal_outside_the_panel()
    {
        $goal = $this->ownedGoal();

        $this->assertFalse($this->admin->can('view', $goal));
        $this->assertFalse($this->admin->can('update', $goal));
        $this->assertFalse($this->admin->can('delete', $goal));
    }

    public function test_admin_cannot_view_update_or_delete_another_users_goal_within_the_panel()
    {
        $goal = $this->ownedGoal();

        $this->enterAdminPanel();

        $this->assertFalse($this->admin->can('view', $goal));
        $this->assertFalse($this->admin->can('update', $goal));
        $this->assertFalse($this->admin->can('delete', $goal));
    }

    public function test_nobody_can_view_any_goal()
    {
        $this->enterAdminPanel();

        $this->assertFalse($this->admin->can('viewAny', Goal::class));
        $this->assertFalse($this->owner->can('viewAny', Goal::class));
    }
}
