<?php

namespace Tests\Feature\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
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

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    protected function admin(): User
    {
        $admin = User::factory()->create();
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

    public function test_admin_can_list_users()
    {
        $admin = $this->admin();
        $user = User::factory()->create(['name' => 'Some Tester']);

        $this->actingAsPanelUser($admin)
            ->get('/admin/users')
            ->assertSuccessful()
            ->assertSee($user->name);
    }

    public function test_it_can_search_users_by_name_and_email()
    {
        $admin = $this->admin();
        $jane = User::factory()->create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);
        $bob = User::factory()->create(['name' => 'Bob Lee', 'email' => 'bob@example.com']);

        Livewire::actingAs($admin)
            ->test(ListUsers::class)
            ->searchTable('jane@example.com')
            ->assertCanSeeTableRecords([$jane])
            ->assertCanNotSeeTableRecords([$bob]);
    }

    public function test_it_can_filter_users_by_verification_status()
    {
        $admin = $this->admin();
        $verified = User::factory()->create();
        $unverified = User::factory()->unverified()->create();

        Livewire::actingAs($admin)
            ->test(ListUsers::class)
            ->filterTable('email_verified_at', false)
            ->assertCanSeeTableRecords([$unverified])
            ->assertCanNotSeeTableRecords([$verified]);
    }

    public function test_it_can_filter_users_by_locale()
    {
        $admin = User::factory()->create(['locale' => 'en']);
        $admin->assignRole('admin');
        $french = User::factory()->create(['locale' => 'fr']);
        $english = User::factory()->create(['locale' => 'en']);

        Livewire::actingAs($admin)
            ->test(ListUsers::class)
            ->filterTable('locale', 'fr')
            ->assertCanSeeTableRecords([$french])
            ->assertCanNotSeeTableRecords([$english]);
    }

    public function test_it_can_filter_users_by_role()
    {
        $admin = $this->admin();
        $role = Role::where('name', 'admin')->first();
        $colleague = User::factory()->create();
        $colleague->assignRole('admin');
        $plain = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(ListUsers::class)
            ->filterTable('roles', $role->id)
            ->assertCanSeeTableRecords([$colleague])
            ->assertCanNotSeeTableRecords([$plain]);
    }

    public function test_it_can_filter_users_by_created_at_range()
    {
        Carbon::setTestNow('2026-07-15');

        $admin = $this->admin();
        $recent = User::factory()->create(['name' => 'Recent', 'created_at' => '2026-07-10']);
        $stale = User::factory()->create(['name' => 'Stale', 'created_at' => '2026-06-01']);

        Livewire::actingAs($admin)
            ->test(ListUsers::class)
            ->filterTable('created_at', [
                'created_from' => '2026-07-01',
                'created_until' => '2026-07-15',
            ])
            ->assertCanSeeTableRecords([$recent])
            ->assertCanNotSeeTableRecords([$stale]);
    }

    public function test_the_verify_action_marks_the_email_as_verified()
    {
        $admin = $this->admin();
        $user = User::factory()->unverified()->create();

        Livewire::actingAs($admin)
            ->test(EditUser::class, ['record' => $user->getKey()])
            ->callAction('verify');

        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_the_verify_action_is_hidden_once_the_email_is_verified()
    {
        $admin = $this->admin();
        $user = User::factory()->create(); // verified by factory default

        Livewire::actingAs($admin)
            ->test(EditUser::class, ['record' => $user->getKey()])
            ->assertActionHidden('verify');
    }

    public function test_the_verify_action_is_visible_for_an_unverified_user()
    {
        // The verify gate enables manual verification only while the email is
        // still unverified.
        $admin = $this->admin();
        $user = User::factory()->unverified()->create();

        Livewire::actingAs($admin)
            ->test(ListUsers::class)
            ->assertTableActionVisible('verify', $user);
    }

    public function test_the_verify_action_is_hidden_for_an_already_verified_user()
    {
        $admin = $this->admin();
        $user = User::factory()->create(); // verified by factory default

        Livewire::actingAs($admin)
            ->test(ListUsers::class)
            ->assertTableActionHidden('verify', $user);
    }

    public function test_an_admin_cannot_delete_their_own_account()
    {
        $admin = $this->admin();

        Livewire::actingAs($admin)
            ->test(ListUsers::class)
            ->assertTableActionHidden('delete', $admin);
    }

    public function test_the_locale_field_defaults_to_a_supported_locale()
    {
        $admin = $this->admin();

        Livewire::actingAs($admin)
            ->test(CreateUser::class)
            ->assertFormSet(fn (array $state): bool => in_array($state['locale'], array_keys(config('locales.supported')))
                && $state['locale'] !== 'UTC');
    }
}
