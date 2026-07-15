<?php

namespace Tests\Feature\Filament\Widgets;

use App\Filament\Widgets\StatsOverviewWidget;
use App\Models\Goal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\WithAdminRole;
use Tests\TestCase;

class StatsOverviewWidgetTest extends TestCase
{
    use RefreshDatabase;
    use WithAdminRole;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpAdminRole();
    }

    public function test_the_stats_widget_renders_with_an_empty_database()
    {
        $admin = User::factory()->withoutTwoFactor()->create();
        $admin->assignRole('admin');

        Livewire::actingAs($admin)
            ->test(StatsOverviewWidget::class)
            ->assertSuccessful();
    }

    public function test_it_computes_the_abandonment_rate()
    {
        $admin = User::factory()->withoutTwoFactor()->create();
        $admin->assignRole('admin');

        // Two abandoned of four total -> rate of 0.5.
        Goal::factory()->count(2)->create(['status' => 'in_progress']);
        Goal::factory()->count(2)->create(['status' => 'abandoned']);

        $expected = round(2 / 4, 2);

        Livewire::actingAs($admin)
            ->test(StatsOverviewWidget::class)
            ->assertSee((string) $expected);
    }
}
