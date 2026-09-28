<?php

namespace App\Services\Encryption;

use App\Exceptions\UserDataEncryptionException;
use App\Models\Goal;
use App\Models\User;
use Illuminate\Encryption\Encrypter;

class UserDataKeyring
{
    /** @var array<string, Encrypter> */
    private array $encryptersByKeyId = [];

    /** @var array<int, string> */
    private array $keyIdsByUserId = [];

    /** @var array<int, int> */
    private array $userIdsByGoalId = [];

    public function __construct(private readonly UserDataKeyStore $keyStore) {}

    /**
     * Encrypter bound to the data key with the given id, unwrapped at most once per request.
     */
    public function encrypterForKey(string $keyId): Encrypter
    {
        return $this->encryptersByKeyId[$keyId] ??= new Encrypter(
            $this->keyStore->unwrap($keyId),
            UserDataKeyStore::CIPHER,
        );
    }

    /**
     * Data key id of the given user.
     */
    public function keyIdForUser(int $userId): string
    {
        return $this->keyIdsByUserId[$userId] ??= User::query()->toBase()
            ->where('id', $userId)
            ->value('data_key_id')
            ?? throw UserDataEncryptionException::userWithoutDataKey($userId);
    }

    /**
     * Owner of the given goal, for rows that only know their goal.
     */
    public function userIdForGoal(int $goalId): int
    {
        return $this->userIdsByGoalId[$goalId] ??= Goal::query()->toBase()
            ->where('id', $goalId)
            ->value('user_id')
            ?? throw UserDataEncryptionException::goalNotFound($goalId);
    }

    /**
     * Drop every cached trace of the given data key.
     */
    public function forget(string $keyId): void
    {
        unset($this->encryptersByKeyId[$keyId]);

        $this->keyIdsByUserId = array_filter(
            $this->keyIdsByUserId,
            fn (string $cachedKeyId): bool => $cachedKeyId !== $keyId,
        );
    }
}
