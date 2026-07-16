<?php

namespace Tests\Feature\Database\Seeders;

use App\Models\User;
use Database\Seeders\RolesTableSeeder;
use Database\Seeders\UsersTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_database_seeder_does_not_create_users_in_production()
    {
        $this->app['env'] = 'production';

        // --force skips the production confirmation prompt so the seeder logic
        // itself is what we are exercising here.
        Artisan::call('db:seed', ['--force' => true]);

        $this->assertDatabaseMissing('users', ['email' => 'admin@example.com']);
        $this->assertDatabaseMissing('users', ['email' => 'test@example.com']);
        $this->assertEquals(0, User::count());
    }

    public function test_the_database_seeder_creates_the_admin_role_in_production()
    {
        $this->app['env'] = 'production';

        Artisan::call('db:seed', ['--force' => true]);

        $this->assertDatabaseHas('roles', ['name' => 'admin']);
    }

    public function test_the_roles_seeder_is_idempotent()
    {
        $this->seed(RolesTableSeeder::class);
        $this->seed(RolesTableSeeder::class);

        $this->assertEquals(1, Role::where('name', 'admin')->count());
    }

    public function test_the_users_seeder_assigns_the_admin_role_outside_production()
    {
        $this->seed(RolesTableSeeder::class);
        $this->seed(UsersTableSeeder::class);

        $admin = User::where('email', 'admin@example.com')->first();

        $this->assertNotNull($admin);
        $this->assertTrue($admin->hasRole('admin'));
    }
}
