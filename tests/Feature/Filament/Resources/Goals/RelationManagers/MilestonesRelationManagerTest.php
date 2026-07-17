<?php

namespace Tests\Feature\Filament\Resources\Goals\RelationManagers;

use App\Filament\Resources\Goals\Pages\ViewGoal;
use App\Filament\Resources\Goals\RelationManagers\MilestonesRelationManager;
use App\Models\Goal;
use App\Models\Milestone;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\WithAdminRole;
use Tests\TestCase;

class MilestonesRelationManagerTest extends TestCase
{
    use RefreshDatabase;
    use WithAdminRole;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpAdminRole();
    }

    public function test_the_goal_milestones_relation_manager_lists_the_goal_milestones()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $goal = Goal::factory()->create();
        $milestone = Milestone::factory()->create(['goal_id' => $goal->id, 'title' => 'First checkpoint']);

        Livewire::actingAs($admin)
            ->test(MilestonesRelationManager::class, [
                'ownerRecord' => $goal,
                'pageClass' => ViewGoal::class,
            ])
            ->assertCanSeeTableRecords([$milestone]);
    }
}
