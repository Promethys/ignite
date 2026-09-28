<?php

namespace App\Services\Encryption;

use App\Exceptions\UserDataEncryptionException;
use App\Models\UserDataKey;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Str;

class UserDataKeyStore
{
    public const CIPHER = 'aes-256-gcm';

    /**
     * Generate a new data key, store it wrapped by the current master key, and return its id.
     */
    public function create(): string
    {
        $dataKey = Encrypter::generateKey(self::CIPHER);
        $masterKey = $this->currentMasterKey();

        $record = UserDataKey::create([
            'wrapped_key' => $this->wrapper($masterKey)->encryptString($dataKey),
            'master_key_id' => $this->fingerprint($masterKey),
        ]);

        return $record->getKey();
    }

    /**
     * Return the raw data key behind the given id.
     */
    public function unwrap(string $keyId): string
    {
        $record = UserDataKey::find($keyId) ?? throw UserDataEncryptionException::missingDataKey($keyId);

        return $this->unwrapRecord($record);
    }

    /**
     * Delete the data key, reporting whether a key was actually removed.
     */
    public function destroy(string $keyId): bool
    {
        return UserDataKey::query()->whereKey($keyId)->delete() > 0;
    }

    /**
     * Re-wrap the data key under the current master key, reporting whether it changed.
     */
    public function rewrap(UserDataKey $record): bool
    {
        $masterKey = $this->currentMasterKey();
        $currentMasterKeyId = $this->fingerprint($masterKey);

        if ($record->master_key_id === $currentMasterKeyId) {
            return false;
        }

        $record->update([
            'wrapped_key' => $this->wrapper($masterKey)->encryptString($this->unwrapRecord($record)),
            'master_key_id' => $currentMasterKeyId,
        ]);

        return true;
    }

    /**
     * Fingerprint of the current master key, as stored on the keys it wraps.
     */
    public function currentMasterKeyId(): string
    {
        return $this->fingerprint($this->currentMasterKey());
    }

    private function unwrapRecord(UserDataKey $record): string
    {
        $masterKey = $this->masterKeysByFingerprint()[$record->master_key_id]
            ?? throw UserDataEncryptionException::unknownMasterKey($record->master_key_id);

        return $this->wrapper($masterKey)->decryptString($record->wrapped_key);
    }

    private function currentMasterKey(): string
    {
        $configured = config('user-data.master_key');

        if (blank($configured)) {
            throw UserDataEncryptionException::missingMasterKey();
        }

        return $this->decodeKey($configured);
    }

    /**
     * @return array<string, string>
     */
    private function masterKeysByFingerprint(): array
    {
        $masterKeys = [$this->currentMasterKey()];

        foreach (config('user-data.previous_master_keys', []) as $previousKey) {
            $masterKeys[] = $this->decodeKey(trim($previousKey));
        }

        return collect($masterKeys)
            ->keyBy(fn (string $masterKey): string => $this->fingerprint($masterKey))
            ->all();
    }

    private function decodeKey(string $key): string
    {
        return Str::startsWith($key, 'base64:')
            ? base64_decode(Str::after($key, 'base64:'))
            : $key;
    }

    private function fingerprint(string $masterKey): string
    {
        return substr(hash('sha256', $masterKey), 0, 16);
    }

    private function wrapper(string $masterKey): Encrypter
    {
        return new Encrypter($masterKey, self::CIPHER);
    }
}
