<?php

namespace Tests\Feature\Services\Encryption;

use App\Exceptions\UserDataEncryptionException;
use App\Models\UserDataKey;
use App\Services\Encryption\UserDataKeyStore;
use Illuminate\Encryption\Encrypter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserDataKeyStoreTest extends TestCase
{
    use RefreshDatabase;

    private UserDataKeyStore $keyStore;

    protected function setUp(): void
    {
        parent::setUp();

        $this->keyStore = app(UserDataKeyStore::class);
    }

    private function newMasterKey(): string
    {
        return 'base64:'.base64_encode(Encrypter::generateKey(UserDataKeyStore::CIPHER));
    }

    public function test_a_created_key_unwraps_to_32_random_bytes()
    {
        $keyId = $this->keyStore->create();

        $this->assertSame(32, strlen($this->keyStore->unwrap($keyId)));
        $this->assertNotSame($this->keyStore->unwrap($keyId), $this->keyStore->unwrap($this->keyStore->create()));
    }

    public function test_the_stored_key_is_wrapped_and_tagged_with_the_master_key_fingerprint()
    {
        $keyId = $this->keyStore->create();
        $record = UserDataKey::find($keyId);

        $this->assertStringNotContainsString($this->keyStore->unwrap($keyId), $record->wrapped_key);
        $this->assertSame($this->keyStore->currentMasterKeyId(), $record->master_key_id);
        $this->assertArrayNotHasKey('wrapped_key', $record->toArray());
    }

    public function test_destroy_removes_the_key_and_reports_whether_one_existed()
    {
        $keyId = $this->keyStore->create();

        $this->assertTrue($this->keyStore->destroy($keyId));
        $this->assertFalse($this->keyStore->destroy($keyId));

        $this->expectException(UserDataEncryptionException::class);
        $this->keyStore->unwrap($keyId);
    }

    public function test_rewrap_moves_a_key_to_the_current_master_key_without_changing_it()
    {
        $keyId = $this->keyStore->create();
        $dataKey = $this->keyStore->unwrap($keyId);

        config([
            'user-data.previous_master_keys' => [config('user-data.master_key')],
            'user-data.master_key' => $this->newMasterKey(),
        ]);

        $this->assertTrue($this->keyStore->rewrap(UserDataKey::find($keyId)));
        $this->assertFalse($this->keyStore->rewrap(UserDataKey::find($keyId)));
        $this->assertSame($this->keyStore->currentMasterKeyId(), UserDataKey::find($keyId)->master_key_id);

        config(['user-data.previous_master_keys' => []]);

        $this->assertSame($dataKey, $this->keyStore->unwrap($keyId));
    }

    public function test_a_key_wrapped_by_an_unconfigured_master_key_names_its_fingerprint()
    {
        $keyId = $this->keyStore->create();
        $fingerprint = UserDataKey::find($keyId)->master_key_id;

        config(['user-data.master_key' => $this->newMasterKey()]);

        $this->expectException(UserDataEncryptionException::class);
        $this->expectExceptionMessage($fingerprint);

        $this->keyStore->unwrap($keyId);
    }

    public function test_a_missing_master_key_fails_loudly()
    {
        config(['user-data.master_key' => null]);

        $this->expectException(UserDataEncryptionException::class);
        $this->expectExceptionMessage('USER_DATA_MASTER_KEY is not set');

        $this->keyStore->create();
    }
}
