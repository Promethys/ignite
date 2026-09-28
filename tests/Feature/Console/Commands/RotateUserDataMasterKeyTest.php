<?php

namespace Tests\Feature\Console\Commands;

use App\Models\Goal;
use App\Models\User;
use App\Models\UserDataKey;
use App\Services\Encryption\UserDataKeyStore;
use Illuminate\Encryption\Encrypter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RotateUserDataMasterKeyTest extends TestCase
{
    use RefreshDatabase;

    private string $previousMasterKeyId;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->create();
        Goal::factory()->count(2)->create(['user_id' => $user->id, 'category_id' => null, 'title' => 'Learn the cello']);
        User::factory()->create();

        $this->previousMasterKeyId = app(UserDataKeyStore::class)->currentMasterKeyId();

        config([
            'user-data.previous_master_keys' => [config('user-data.master_key')],
            'user-data.master_key' => 'base64:'.base64_encode(Encrypter::generateKey(UserDataKeyStore::CIPHER)),
        ]);
        app()->forgetScopedInstances();
    }

    public function test_check_reports_the_keys_still_on_a_previous_master_key_without_changing_them()
    {
        $this->artisan('app:rotate-user-data-master-key --check')
            ->expectsOutputToContain($this->previousMasterKeyId)
            ->expectsOutputToContain('Keep USER_DATA_PREVIOUS_MASTER_KEYS')
            ->assertSuccessful();

        $this->assertSame(2, UserDataKey::where('master_key_id', $this->previousMasterKeyId)->count());
    }

    public function test_it_rewraps_every_key_under_the_current_master_key()
    {
        $this->artisan('app:rotate-user-data-master-key')
            ->expectsOutputToContain('Data keys re-wrapped: 2')
            ->expectsOutputToContain('can be emptied')
            ->assertSuccessful();

        $this->assertSame(0, UserDataKey::where('master_key_id', $this->previousMasterKeyId)->count());
    }

    public function test_a_second_run_rewraps_nothing()
    {
        $this->artisan('app:rotate-user-data-master-key');

        $this->artisan('app:rotate-user-data-master-key')
            ->expectsOutputToContain('Data keys re-wrapped: 0')
            ->assertSuccessful();
    }

    public function test_the_data_reads_with_the_new_master_key_alone_after_rotation()
    {
        $this->artisan('app:rotate-user-data-master-key');

        config(['user-data.previous_master_keys' => []]);
        app()->forgetScopedInstances();

        $this->assertSame(['Learn the cello', 'Learn the cello'], Goal::all()->pluck('title')->all());
    }
}
