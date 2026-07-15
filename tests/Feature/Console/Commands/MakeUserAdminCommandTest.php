<?php

namespace Tests\Feature\Console\Commands;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MakeUserAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::findOrCreate('admin');
    }

    public function test_it_assigns_the_admin_role_to_a_user_by_email()
    {
        $user = User::factory()->create(['email' => 'jane@example.com']);

        $this->artisan('app:make-admin', ['user' => 'jane@example.com', '--force' => true])
            ->assertSuccessful();

        $this->assertTrue($user->fresh()->hasRole('admin'));
    }

    public function test_it_assigns_the_admin_role_to_a_user_by_id()
    {
        $user = User::factory()->create();

        $this->artisan('app:make-admin', ['user' => $user->id, '--force' => true])
            ->assertSuccessful();

        $this->assertTrue($user->fresh()->hasRole('admin'));
    }

    public function test_it_fails_when_the_user_does_not_exist()
    {
        $this->artisan('app:make-admin', ['user' => 'ghost@example.com', '--force' => true])
            ->assertFailed();

        $this->assertDatabaseMissing('model_has_roles', ['role_id' => Role::where('name', 'admin')->value('id')]);
    }

    public function test_it_is_idempotent_when_the_user_is_already_an_admin()
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $this->artisan('app:make-admin', ['user' => $user->email, '--force' => true])
            ->assertSuccessful();

        $this->assertCount(1, $user->fresh()->roles);
    }

    public function test_it_aborts_without_force_when_not_interactive()
    {
        // A known name keeps the confirmation prompt deterministic.
        $user = User::factory()->create(['name' => 'Tim', 'email' => 'tim@example.com']);

        $this->artisan('app:make-admin', ['user' => 'tim@example.com'])
            ->expectsConfirmation("Admin role will be assigned to 'Tim'.Do you wish to continue?", 'no')
            ->assertFailed();

        $this->assertFalse($user->fresh()->hasRole('admin'));
    }
}
