<?php

namespace Tests\Feature\Filament\Resources\Goals;

use App\Filament\Resources\Goals\GoalResource;
use App\Filament\Resources\Goals\Pages\ListGoals;
use App\Models\Goal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
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
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        Goal::factory()->create(['title' => 'Listable Goal']);

        $this->actingAsPanelUser($admin)
            ->get('/admin/goals')
            ->assertSuccessful();
    }

    public function test_admin_can_view_a_goal_owned_by_another_user()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $owner = User::factory()->create();
        $goal = Goal::factory()->create(['user_id' => $owner->id, 'title' => 'Not mine']);

        $this->actingAsPanelUser($admin)
            ->get("/admin/goals/{$goal->id}")
            ->assertSuccessful();
    }

    public function test_a_non_admin_cannot_list_goals_in_the_panel()
    {
        $user = User::factory()->create();
        Goal::factory()->create(['user_id' => $user->id]);

        $this->actingAsPanelUser($user)
            ->get('/admin/goals')
            ->assertForbidden();
    }

    public function test_the_goal_resource_exposes_no_create_or_edit_page()
    {
        $pages = array_keys(GoalResource::getPages());

        $this->assertNotContains('create', $pages);
        $this->assertNotContains('edit', $pages);
    }

    public function test_it_can_filter_goals_by_status()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $abandoned = Goal::factory()->create(['status' => 'abandoned', 'title' => 'Dropped']);
        $active = Goal::factory()->create(['status' => 'in_progress', 'title' => 'Active']);

        Livewire::actingAs($admin)
            ->test(ListGoals::class)
            ->filterTable('status', 'abandoned')
            ->assertCanSeeTableRecords([$abandoned])
            ->assertCanNotSeeTableRecords([$active]);
    }

    public function test_it_can_filter_goals_by_type_and_user()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $owner = User::factory()->create();
        $simple = Goal::factory()->create(['user_id' => $owner->id, 'type' => 'simple', 'title' => 'Simple']);
        $quantifiable = Goal::factory()->create(['user_id' => $admin->id, 'type' => 'quantifiable', 'title' => 'Other']);

        Livewire::actingAs($admin)
            ->test(ListGoals::class)
            ->filterTable('type', 'simple')
            ->filterTable('user', $owner->id)
            ->assertCanSeeTableRecords([$simple])
            ->assertCanNotSeeTableRecords([$quantifiable]);
    }
}
