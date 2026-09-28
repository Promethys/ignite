<?php

namespace Tests\Feature\Services\Encryption;

use App\Exceptions\UserDataEncryptionException;
use App\Services\Encryption\UserDataCipher;
use App\Services\Encryption\UserDataKeyring;
use App\Services\Encryption\UserDataKeyStore;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserDataCipherTest extends TestCase
{
    use RefreshDatabase;

    private UserDataCipher $cipher;

    private string $keyId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cipher = app(UserDataCipher::class);
        $this->keyId = app(UserDataKeyStore::class)->create();
    }

    public function test_it_round_trips_a_value_behind_the_version_prefix()
    {
        $stored = $this->cipher->encrypt('Première sortie', $this->keyId);

        $this->assertStringStartsWith(UserDataCipher::PREFIX, $stored);
        $this->assertStringNotContainsString('Première', $stored);
        $this->assertSame('Première sortie', $this->cipher->decrypt($stored, $this->keyId));
    }

    public function test_the_same_value_encrypts_differently_each_time()
    {
        $this->assertNotSame(
            $this->cipher->encrypt('Run a marathon', $this->keyId),
            $this->cipher->encrypt('Run a marathon', $this->keyId),
        );
    }

    public function test_an_unprefixed_value_is_rejected_instead_of_returned()
    {
        $this->expectException(UserDataEncryptionException::class);

        $this->cipher->decrypt('Run a marathon', $this->keyId);
    }

    public function test_another_users_key_cannot_decrypt_the_value()
    {
        $stored = $this->cipher->encrypt('Run a marathon', $this->keyId);

        $this->expectException(DecryptException::class);

        $this->cipher->decrypt($stored, app(UserDataKeyStore::class)->create());
    }

    public function test_the_keyring_is_shared_within_a_request_and_dropped_after_it()
    {
        $keyring = app(UserDataKeyring::class);

        $this->assertSame($keyring, app(UserDataKeyring::class));

        app()->forgetScopedInstances();

        $this->assertNotSame($keyring, app(UserDataKeyring::class));
    }
}
