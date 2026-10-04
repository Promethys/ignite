<?php

namespace App\Ai\Storage;

use App\Models\User;
use App\Services\Encryption\UserDataCipher;
use App\Services\Encryption\UserDataKeyring;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use LogicException;
use stdClass;

class EncryptingQueryBuilder extends Builder
{
    private const OWNER_COLUMNS = ['participant_type', 'participant_id'];

    /** @var list<string> */
    private array $encryptedColumns = [];

    /**
     * @param  list<string>  $columns
     */
    public function encrypting(array $columns): static
    {
        $this->encryptedColumns = $columns;

        return $this;
    }

    /**
     * Insert rows with their encrypted columns encrypted under each row's participant.
     */
    public function insert(array $values)
    {
        if ($values === []) {
            return parent::insert($values);
        }

        $rows = array_is_list($values) ? $values : [$values];

        return parent::insert(array_map(
            fn (array $row): array => $this->encryptValues(
                $row,
                $this->keyIdFor($row['participant_type'] ?? null, $row['participant_id'] ?? null),
            ),
            $rows,
        ));
    }

    /**
     * Update the matched rows, encrypting under the participant they belong to.
     */
    public function update(array $values)
    {
        if (! $this->touchesEncryptedColumns(array_keys($values))) {
            return parent::update($values);
        }

        $owners = (clone $this)->matchedOwners();

        if ($owners->isEmpty()) {
            return 0;
        }

        if ($owners->count() > 1) {
            throw new LogicException('An encrypted conversation update must target the rows of one participant.');
        }

        $owner = $owners->first();

        return parent::update($this->encryptValues(
            $values,
            $this->keyIdFor($owner->participant_type, $owner->participant_id),
        ));
    }

    /**
     * Run the query and decrypt the encrypted columns of each row.
     */
    public function get($columns = ['*'])
    {
        $columns = Arr::wrap($columns);

        if ($columns !== ['*'] && $this->touchesEncryptedColumns($columns)) {
            $columns = array_values(array_unique([...$columns, ...self::OWNER_COLUMNS]));
        }

        return parent::get($columns)->map(fn (stdClass $row): stdClass => $this->decryptRow($row));
    }

    /**
     * @return Collection<int, stdClass>
     */
    private function matchedOwners(): Collection
    {
        return parent::get(self::OWNER_COLUMNS)
            ->unique(fn (stdClass $row): string => $row->participant_type.':'.$row->participant_id)
            ->values();
    }

    /**
     * @param  list<int|string>  $columns
     */
    private function touchesEncryptedColumns(array $columns): bool
    {
        return array_intersect($columns, $this->encryptedColumns) !== [];
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    private function encryptValues(array $values, string $keyId): array
    {
        $cipher = app(UserDataCipher::class);

        foreach ($this->encryptedColumns as $column) {
            if (isset($values[$column])) {
                $values[$column] = $cipher->encrypt((string) $values[$column], $keyId);
            }
        }

        return $values;
    }

    private function decryptRow(stdClass $row): stdClass
    {
        $present = array_filter(
            $this->encryptedColumns,
            fn (string $column): bool => isset($row->{$column}),
        );

        if ($present === []) {
            return $row;
        }

        $keyId = $this->keyIdFor($row->participant_type ?? null, $row->participant_id ?? null);
        $cipher = app(UserDataCipher::class);

        foreach ($present as $column) {
            $row->{$column} = $cipher->decrypt($row->{$column}, $keyId);
        }

        return $row;
    }

    private function keyIdFor(?string $participantType, string|int|null $participantId): string
    {
        if ($participantId === null || $participantType !== (new User)->getMorphClass()) {
            throw new LogicException('A conversation can only be stored for a user, whose key encrypts it.');
        }

        return app(UserDataKeyring::class)->keyIdForUser((int) $participantId);
    }
}
