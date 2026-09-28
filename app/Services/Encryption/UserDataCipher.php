<?php

namespace App\Services\Encryption;

use App\Exceptions\UserDataEncryptionException;
use Illuminate\Support\Str;

class UserDataCipher
{
    public const PREFIX = 'v1:';

    public function __construct(private readonly UserDataKeyring $keyring) {}

    public function encrypt(string $value, string $keyId): string
    {
        return self::PREFIX.$this->keyring->encrypterForKey($keyId)->encryptString($value);
    }

    public function decrypt(string $storedValue, string $keyId): string
    {
        if (! self::isEncrypted($storedValue)) {
            throw UserDataEncryptionException::unencryptedValue();
        }

        return $this->keyring->encrypterForKey($keyId)->decryptString(Str::after($storedValue, self::PREFIX));
    }

    public static function isEncrypted(string $storedValue): bool
    {
        return Str::startsWith($storedValue, self::PREFIX);
    }
}
