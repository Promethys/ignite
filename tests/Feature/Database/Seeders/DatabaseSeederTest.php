<?php

namespace Tests\Feature\Database\Seeders;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
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
}
