<?php

namespace App\Traits\Models;

use App\Services\Encryption\UserDataCipher;

trait EncryptsUserData
{
    /**
     * @return list<string>
     */
    abstract protected function encryptedAttributes(): array;

    abstract protected function userDataKeyId(): string;

    public static function bootEncryptsUserData(): void
    {
        static::retrieved(fn (self $model) => $model->decryptUserData());
    }

    /**
     * @param  string  $key
     * @return bool
     */
    public function hasAnyGetMutator($key)
    {
        return in_array($key, $this->encryptedAttributes(), true) || parent::hasAnyGetMutator($key);
    }

    /**
     * @return array<string, mixed>
     */
    protected function getAttributesForInsert()
    {
        return $this->encryptForStorage(parent::getAttributesForInsert());
    }

    /**
     * @return array<string, mixed>
     */
    protected function getDirtyForUpdate()
    {
        return $this->encryptForStorage(parent::getDirtyForUpdate());
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function encryptForStorage(array $attributes): array
    {
        $toEncrypt = array_filter(
            array_intersect_key($attributes, array_flip($this->encryptedAttributes())),
            fn (mixed $value): bool => $value !== null,
        );

        if ($toEncrypt === []) {
            return $attributes;
        }

        $cipher = app(UserDataCipher::class);
        $keyId = $this->userDataKeyId();

        foreach ($toEncrypt as $attribute => $value) {
            $attributes[$attribute] = $cipher->encrypt((string) $value, $keyId);
        }

        return $attributes;
    }

    private function decryptUserData(): void
    {
        $toDecrypt = array_filter(
            array_intersect_key($this->attributes, array_flip($this->encryptedAttributes())),
            fn (mixed $value): bool => $value !== null,
        );

        if ($toDecrypt === []) {
            return;
        }

        $cipher = app(UserDataCipher::class);
        $keyId = $this->userDataKeyId();

        foreach ($toDecrypt as $attribute => $storedValue) {
            $this->attributes[$attribute] = $cipher->decrypt($storedValue, $keyId);
        }

        $this->syncOriginalAttributes(array_keys($toDecrypt));
    }
}
