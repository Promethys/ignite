<?php

namespace Tests\Feature\Providers\Filament;

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\WithAdminRole;
use Tests\TestCase;

class AdminPanelProviderTest extends TestCase
{
    use RefreshDatabase;
    use WithAdminRole;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpAdminRole();
    }

    public function test_guest_cannot_access_the_admin_panel()
    {
        $this->get('/admin')->assertRedirect(route('login'));
    }

    public function test_user_without_admin_role_cannot_access_the_admin_panel()
    {
        $user = User::factory()->create();

        $this->actingAsPanelUser($user)
            ->get('/admin')
            ->assertForbidden();
    }

    public function test_user_with_admin_role_can_access_the_admin_panel()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAsPanelUser($admin)
            ->get('/admin')
            ->assertSuccessful();
    }

    public function test_can_access_panel_returns_false_without_the_admin_role()
    {
        $panel = Filament::getPanel('admin');
        $user = User::factory()->create();

        $this->assertFalse($user->canAccessPanel($panel));

        $user->assignRole('admin');

        $this->assertTrue($user->canAccessPanel($panel));
    }

    public function test_unverified_admin_can_still_access_the_panel()
    {
        $admin = User::factory()->unverified()->create();
        $admin->assignRole('admin');

        $this->actingAsPanelUser($admin)
            ->get('/admin')
            ->assertSuccessful();
    }
}
