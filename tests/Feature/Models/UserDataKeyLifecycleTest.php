<?php

namespace Tests\Feature\Models;

use App\Ai\Agents\IgniteAssistant;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\AssistantKey;
use App\Models\User;
use App\Models\UserDataKey;
use App\Services\Auth\SocialLoginService;
use App\Services\Encryption\UserDataCipher;
use App\Services\Encryption\UserDataKeyStore;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Socialite\Two\User as SocialiteUser;
use Livewire\Livewire;
use Mockery\MockInterface;
use RuntimeException;
use Tests\Concerns\WithAdminRole;
use Tests\TestCase;

class UserDataKeyLifecycleTest extends TestCase
{
    use RefreshDatabase;
    use WithAdminRole;

    private function assertHasDataKey(User $user): void
    {
        $this->assertNotNull($user->data_key_id);
        $this->assertNotNull(UserDataKey::find($user->data_key_id));
        $this->assertStringStartsWith(UserDataCipher::PREFIX, DB::table('users')->where('id', $user->id)->value('name'));
    }

    // =========================================================================
    // CREATION
    // =========================================================================

    public function test_registration_creates_a_key_and_encrypts_the_default_categories()
    {
        $this->post(route('register.store'), [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'Strong-P@ssw0rd',
            'password_confirmation' => 'Strong-P@ssw0rd',
        ]);

        $user = User::where('email', 'jane@example.com')->firstOrFail();

        $this->assertHasDataKey($user);
        $this->assertSame('Jane Doe', $user->name);
        $this->assertNotEmpty($user->categories);
        $this->assertTrue(
            DB::table('categories')->where('user_id', $user->id)->pluck('name')
                ->every(fn (string $name): bool => str_starts_with($name, UserDataCipher::PREFIX)),
        );
    }

    public function test_social_login_creates_a_key()
    {
        $attributes = ['id' => '12345', 'nickname' => 'janedoe', 'name' => 'Jane Doe', 'email' => 'jane@example.com', 'avatar' => null, 'verified_email' => true];
        $socialiteUser = (new SocialiteUser)->setRaw($attributes)->map($attributes);

        $user = app(SocialLoginService::class)->resolveUser('google', $socialiteUser);

        $this->assertHasDataKey($user);
    }

    public function test_the_admin_panel_creates_a_key()
    {
        $this->setUpAdminRole();
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin);
        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'Created By Admin',
                'email' => 'created@example.com',
                'locale' => 'en',
                'timezone' => 'UTC',
                'password' => 'Strong-P@ssw0rd',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertHasDataKey(User::where('email', 'created@example.com')->firstOrFail());
    }

    // =========================================================================
    // DELETION
    // =========================================================================

    public function test_deleting_an_account_destroys_its_key()
    {
        $user = User::factory()->create();
        $keyId = $user->data_key_id;

        $user->delete();

        $this->assertNull(UserDataKey::find($keyId));
    }

    public function test_deleting_an_account_deletes_its_assistant_conversations()
    {
        IgniteAssistant::fake(['Fine.', 'Fine.']);
        $user = User::factory()->create();
        $other = User::factory()->create();

        foreach ([$user, $other] as $participant) {
            AssistantKey::factory()->create(['user_id' => $participant->id]);
            $this->actingAs($participant)->postJson(route('assistant.chat'), [
                'messages' => [['id' => 'user-1', 'role' => 'user', 'parts' => [['type' => 'text', 'text' => 'How am I doing?']]]],
            ])->streamedContent();
        }

        $user->delete();

        $this->assertDatabaseMissing('agent_conversations', ['participant_id' => $user->id]);
        $this->assertDatabaseMissing('agent_conversation_messages', ['participant_id' => $user->id]);
        $this->assertDatabaseCount('agent_conversations', 1);
        $this->assertDatabaseCount('agent_conversation_messages', 2);
    }

    public function test_the_profile_deletion_destroys_the_key()
    {
        $user = User::factory()->create();
        $keyId = $user->data_key_id;

        $this->actingAs($user)
            ->delete(route('profile.destroy'), ['password' => 'password']);

        $this->assertModelMissing($user);
        $this->assertNull(UserDataKey::find($keyId));
    }

    public function test_the_admin_bulk_delete_destroys_every_selected_key()
    {
        $this->setUpAdminRole();
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $first = User::factory()->create();
        $second = User::factory()->create();

        $this->actingAs($admin);
        Livewire::test(ListUsers::class)
            ->selectTableRecords([$first, $second])
            ->callAction(TestAction::make('delete')->table()->bulk());

        $this->assertModelMissing($first);
        $this->assertNull(UserDataKey::find($first->data_key_id));
        $this->assertNull(UserDataKey::find($second->data_key_id));
    }

    public function test_a_key_store_failure_aborts_the_deletion()
    {
        $user = User::factory()->create();

        $this->mock(UserDataKeyStore::class, function (MockInterface $keyStore): void {
            $keyStore->shouldReceive('destroy')->andThrow(new RuntimeException('Key database unreachable'));
        });

        try {
            $user->delete();
            $this->fail('The deletion should have been aborted.');
        } catch (RuntimeException) {
            $this->assertModelExists($user);
        }
    }

    public function test_an_already_missing_key_does_not_block_the_deletion()
    {
        $user = User::factory()->create();
        UserDataKey::query()->whereKey($user->data_key_id)->delete();

        $user->delete();

        $this->assertModelMissing($user);
    }
}
