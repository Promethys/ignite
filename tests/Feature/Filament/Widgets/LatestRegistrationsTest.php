<?php

namespace Tests\Feature\Filament\Widgets;

use App\Filament\Widgets\LatestRegistrations;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\WithAdminRole;
use Tests\TestCase;

class LatestRegistrationsTest extends TestCase
{
    use RefreshDatabase;
    use WithAdminRole;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpAdminRole();
    }

    public function test_it_lists_new_accounts_by_email_without_their_name()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $newcomer = User::factory()->create(['name' => 'Private Name', 'email' => 'newcomer@example.com']);

        Livewire::actingAs($admin)
            ->test(LatestRegistrations::class)
            ->assertCanSeeTableRecords([$newcomer])
            ->assertSee('newcomer@example.com')
            ->assertDontSee('Private Name');
    }
}
