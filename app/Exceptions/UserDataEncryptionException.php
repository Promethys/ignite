<?php

namespace App\Exceptions;

use RuntimeException;

class UserDataEncryptionException extends RuntimeException
{
    public static function missingMasterKey(): self
    {
        return new self('USER_DATA_MASTER_KEY is not set. Generate one with "php artisan key:generate --show".');
    }

    public static function unknownMasterKey(string $masterKeyId): self
    {
        return new self("No configured master key matches fingerprint {$masterKeyId}. Add the key that wrapped it to USER_DATA_PREVIOUS_MASTER_KEYS.");
    }

    public static function missingDataKey(string $keyId): self
    {
        return new self("User data key {$keyId} does not exist.");
    }

    public static function userWithoutDataKey(int $userId): self
    {
        return new self("User {$userId} has no data key.");
    }

    public static function goalNotFound(int $goalId): self
    {
        return new self("Goal {$goalId} does not exist, so its owner's key cannot be resolved.");
    }

    public static function unencryptedValue(): self
    {
        return new self('Found an unencrypted value in an encrypted column. Run "php artisan user-data:encrypt-existing".');
    }

    public static function missingOwner(string $model): self
    {
        return new self("Cannot resolve the owner of this {$model}, so its data key is unknown.");
    }
}
