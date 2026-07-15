<?php

namespace Tests\Feature\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\EditUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\Concerns\WithAdminRole;
use Tests\TestCase;

class UserResourceTest extends TestCase
{
    use RefreshDatabase;
    use WithAdminRole;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpAdminRole();
    }

    protected function admin(): User
    {
        $admin = User::factory()->withoutTwoFactor()->create();
        $admin->assignRole('admin');

        return $admin;
    }

    public function test_editing_a_user_without_a_new_password_keeps_the_existing_password()
    {
        $user = User::factory()->create(['password' => Hash::make('secret123')]);
        $originalHash = $user->password;

        Livewire::actingAs($this->admin())
            ->test(EditUser::class, ['record' => $user->getKey()])
            ->fillForm([
                'name' => $user->name,
                'email' => $user->email,
                'locale' => 'en',
                'timezone' => 'UTC',
                'password' => '',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $fresh = $user->fresh();

        // The hash must be untouched and the user must still authenticate.
        $this->assertSame($originalHash, $fresh->password);
        $this->assertTrue(Hash::check('secret123', $fresh->password));
    }

    public function test_it_hashes_a_new_password_when_one_is_provided()
    {
        $user = User::factory()->create();

        Livewire::actingAs($this->admin())
            ->test(EditUser::class, ['record' => $user->getKey()])
            ->fillForm([
                'name' => $user->name,
                'email' => $user->email,
                'locale' => 'en',
                'timezone' => 'UTC',
                'password' => 'brand-new-password',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $fresh = $user->fresh();

        $this->assertNotSame('brand-new-password', $fresh->password);
        $this->assertTrue(Hash::check('brand-new-password', $fresh->password));
    }

    public function test_it_rejects_a_duplicate_email()
    {
        User::factory()->create(['email' => 'taken@example.com']);
        $user = User::factory()->create(['email' => 'mine@example.com']);

        Livewire::actingAs($this->admin())
            ->test(EditUser::class, ['record' => $user->getKey()])
            ->fillForm([
                'name' => $user->name,
                'email' => 'taken@example.com',
                'locale' => 'en',
                'timezone' => 'UTC',
            ])
            ->call('save')
            ->assertHasFormErrors(['email' => 'unique']);
    }

    public function test_it_allows_saving_a_user_with_their_own_unchanged_email()
    {
        $user = User::factory()->create(['email' => 'mine@example.com']);

        Livewire::actingAs($this->admin())
            ->test(EditUser::class, ['record' => $user->getKey()])
            ->fillForm([
                'name' => 'Renamed',
                'email' => 'mine@example.com',
                'locale' => 'en',
                'timezone' => 'UTC',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertEquals('Renamed', $user->fresh()->name);
    }
}
