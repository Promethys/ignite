<?php

namespace Tests\Feature\Filament\Resources\Goals\RelationManagers;

use App\Filament\Resources\Goals\Pages\ViewGoal;
use App\Filament\Resources\Goals\RelationManagers\EntriesRelationManager;
use App\Models\Goal;
use App\Models\GoalEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\WithAdminRole;
use Tests\TestCase;

class EntriesRelationManagerTest extends TestCase
{
    use RefreshDatabase;
    use WithAdminRole;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpAdminRole();
    }

    public function test_the_goal_entries_relation_manager_lists_the_goal_entries()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $goal = Goal::factory()->create();
        $entry = GoalEntry::factory()->create(['goal_id' => $goal->id, 'note' => 'Logged progress']);

        Livewire::actingAs($admin)
            ->test(EntriesRelationManager::class, [
                'ownerRecord' => $goal,
                'pageClass' => ViewGoal::class,
            ])
            ->assertCanSeeTableRecords([$entry]);
    }
}
