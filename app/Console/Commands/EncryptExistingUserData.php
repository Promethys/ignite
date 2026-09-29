<?php

namespace App\Console\Commands;

use App\Services\Encryption\UserDataCipher;
use App\Services\Encryption\UserDataKeyring;
use App\Services\Encryption\UserDataKeyStore;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

#[Signature('app:encrypt-existing-user-data')]
#[Description('Encrypt user content stored before encryption was enabled')]
class EncryptExistingUserData extends Command
{
    /** @var array<string, int> */
    private array $encryptedCounts = [];

    private int $assignedKeys = 0;

    public function handle(UserDataKeyStore $keyStore, UserDataCipher $cipher, UserDataKeyring $keyring): int
    {
        $this->encryptedCounts = [];
        $this->assignedKeys = 0;

        DB::table('users')->select(['id', 'data_key_id'])->orderBy('id')->chunkById(100, function ($users) use ($keyStore, $cipher, $keyring): void {
            foreach ($users as $user) {
                $keyId = $user->data_key_id ?? $this->assignKey($user->id, $keyStore);

                DB::transaction(fn () => $this->encryptUserRows($user->id, $keyId, $cipher));

                $keyring->forget($keyId);
            }
        });

        $this->info("Data keys assigned: {$this->assignedKeys}");
        $this->table(
            ['Column', 'Values encrypted'],
            collect($this->encryptedCounts)->map(fn (int $count, string $column): array => [$column, $count])->values()->all(),
        );

        return self::SUCCESS;
    }

    private function assignKey(int $userId, UserDataKeyStore $keyStore): string
    {
        $keyId = $keyStore->create();

        DB::table('users')->where('id', $userId)->update(['data_key_id' => $keyId]);
        $this->assignedKeys++;

        return $keyId;
    }

    private function encryptUserRows(int $userId, string $keyId, UserDataCipher $cipher): void
    {
        foreach ($this->rowsOwnedBy($userId) as [$table, $columns, $query]) {
            $query->select(['id', ...$columns])->lazyById(500)->each(function (object $row) use ($table, $columns, $keyId, $cipher): void {
                $changes = [];

                foreach ($columns as $column) {
                    $value = $row->{$column};

                    if ($value !== null && $this->needsEncryption($value, $keyId, $cipher)) {
                        $changes[$column] = $cipher->encrypt($value, $keyId);
                        $this->encryptedCounts["{$table}.{$column}"] = ($this->encryptedCounts["{$table}.{$column}"] ?? 0) + 1;
                    }
                }

                if ($changes !== []) {
                    DB::table($table)->where('id', $row->id)->update($changes);
                }
            });
        }
    }

    /**
     * @return list<array{string, list<string>, Builder}>
     */
    private function rowsOwnedBy(int $userId): array
    {
        $goalIds = DB::table('goals')->select('id')->where('user_id', $userId);

        return [
            ['users', ['name'], DB::table('users')->where('id', $userId)],
            ['goals', ['title', 'description', 'unit'], DB::table('goals')->where('user_id', $userId)],
            ['categories', ['name', 'description'], DB::table('categories')->where('user_id', $userId)],
            ['goal_entries', ['note'], DB::table('goal_entries')->whereIn('goal_id', $goalIds)],
            ['milestones', ['title', 'description'], DB::table('milestones')->whereIn('goal_id', $goalIds)],
        ];
    }

    private function needsEncryption(string $value, string $keyId, UserDataCipher $cipher): bool
    {
        if (! UserDataCipher::isEncrypted($value)) {
            return true;
        }

        try {
            $cipher->decrypt($value, $keyId);

            return false;
        } catch (DecryptException) {
            return true;
        }
    }
}
