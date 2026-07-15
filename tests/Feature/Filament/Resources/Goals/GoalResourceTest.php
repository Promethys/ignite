<?php

namespace Tests\Feature\Filament\Resources\Goals;

use App\Models\Goal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\WithAdminRole;
use Tests\TestCase;

class GoalResourceTest extends TestCase
{
    use RefreshDatabase;
    use WithAdminRole;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpAdminRole();
    }

    public function test_admin_can_list_goals()
    {
        $admin = User::factory()->withoutTwoFactor()->create();
        $admin->assignRole('admin');
        Goal::factory()->create(['title' => 'Listable Goal']);

        $this->actingAsPanelUser($admin)
            ->get('/admin/goals')
            ->assertSuccessful();
    }

    public function test_admin_can_view_a_goal_owned_by_another_user()
    {
        $admin = User::factory()->withoutTwoFactor()->create();
        $admin->assignRole('admin');

        $owner = User::factory()->create();
        $goal = Goal::factory()->create(['user_id' => $owner->id, 'title' => 'Not mine']);

        $this->actingAsPanelUser($admin)
            ->get("/admin/goals/{$goal->id}")
            ->assertSuccessful();
    }

    public function test_a_non_admin_cannot_list_goals_in_the_panel()
    {
        $user = User::factory()->withoutTwoFactor()->create();
        Goal::factory()->create(['user_id' => $user->id]);

        $this->actingAsPanelUser($user)
            ->get('/admin/goals')
            ->assertForbidden();
    }
}
