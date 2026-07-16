<?php

namespace Tests\Feature\Filament\Resources\Users\RelationManagers;

use App\Filament\Resources\Users\Pages\ViewUser;
use App\Filament\Resources\Users\RelationManagers\GoalsRelationManager;
use App\Models\Goal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\WithAdminRole;
use Tests\TestCase;

class GoalsRelationManagerTest extends TestCase
{
    use RefreshDatabase;
    use WithAdminRole;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpAdminRole();
    }

    public function test_the_user_goals_relation_manager_lists_only_that_users_goals()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $owner = User::factory()->create();
        $owned = Goal::factory()->create(['user_id' => $owner->id, 'title' => 'Owners Goal']);
        $leaked = Goal::factory()->create(['title' => 'Someone Elses Goal']);

        Livewire::actingAs($admin)
            ->test(GoalsRelationManager::class, [
                'ownerRecord' => $owner,
                'pageClass' => ViewUser::class,
            ])
            ->assertCanSeeTableRecords([$owned])
            ->assertCanNotSeeTableRecords([$leaked]);
    }
}
