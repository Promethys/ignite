<?php

namespace App\Console\Commands;

use App\Models\UserDataKey;
use App\Services\Encryption\UserDataKeyStore;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:rotate-user-data-master-key {--check : Only report how many data keys each master key still wraps}')]
#[Description('Re-wrap every user data key under the current master key')]
class RotateUserDataMasterKey extends Command
{
    public function handle(UserDataKeyStore $keyStore): int
    {
        $currentMasterKeyId = $keyStore->currentMasterKeyId();

        if (! $this->option('check')) {
            $rewrapped = 0;

            UserDataKey::query()
                ->where('master_key_id', '!=', $currentMasterKeyId)
                ->lazyById(200)
                ->each(function (UserDataKey $record) use ($keyStore, &$rewrapped): void {
                    if ($keyStore->rewrap($record)) {
                        $rewrapped++;
                    }
                });

            $this->info("Data keys re-wrapped: {$rewrapped}");
        }

        $keysByMasterKey = UserDataKey::query()->toBase()
            ->selectRaw('master_key_id, count(*) as key_count')
            ->groupBy('master_key_id')
            ->pluck('key_count', 'master_key_id');

        $this->table(
            ['Master key fingerprint', 'Data keys'],
            $keysByMasterKey->map(fn (int $count, string $masterKeyId): array => [
                $masterKeyId === $currentMasterKeyId ? "{$masterKeyId} (current)" : $masterKeyId,
                $count,
            ])->values()->all(),
        );

        if ($keysByMasterKey->except($currentMasterKeyId)->isEmpty()) {
            $this->info('No data key depends on a previous master key. USER_DATA_PREVIOUS_MASTER_KEYS can be emptied.');
        } else {
            $this->warn('Some data keys still depend on a previous master key. Keep USER_DATA_PREVIOUS_MASTER_KEYS until this reaches zero.');
        }

        return self::SUCCESS;
    }
}
