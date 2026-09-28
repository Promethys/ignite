<?php

namespace Tests\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

trait AssertsUserData
{
    /**
     * @param  class-string<Model>  $model
     * @param  array<string, mixed>  $attributes
     */
    protected function assertUserDataHas(string $model, array $attributes): void
    {
        $this->assertNotEmpty(
            $this->modelsMatching($model, $attributes),
            "No [{$model}] matches ".json_encode($attributes).' after decryption.',
        );
    }

    /**
     * @param  class-string<Model>  $model
     * @param  array<string, mixed>  $attributes
     */
    protected function assertUserDataMissing(string $model, array $attributes): void
    {
        $this->assertEmpty(
            $this->modelsMatching($model, $attributes),
            "A [{$model}] matches ".json_encode($attributes).' after decryption.',
        );
    }

    /**
     * @param  class-string<Model>  $model
     * @param  array<string, mixed>  $attributes
     * @return Collection<int, Model>
     */
    private function modelsMatching(string $model, array $attributes): Collection
    {
        return $model::query()->withoutGlobalScopes()->get()->filter(
            fn (Model $record): bool => collect($attributes)->every(
                fn (mixed $value, string $attribute): bool => $record->getAttribute($attribute) == $value,
            ),
        )->values();
    }
}
