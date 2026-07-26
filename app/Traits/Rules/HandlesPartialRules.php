<?php

namespace App\Traits\Rules;

trait HandlesPartialRules
{
    /**
     * Same as `rules()`, but nothing is required. For partial updates.
     *
     * @return array<string, string>
     */
    public static function partialRules(): array
    {
        return array_map(
            static fn (string $rule) => str_replace('required|', 'nullable|', $rule),
            self::rules(),
        );
    }
}
