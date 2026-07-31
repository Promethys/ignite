<?php

namespace App\Traits\Rules;

trait HandlesPartialRules
{
    /**
     * Same as `rules()`, but nothing is required. For partial updates.
     *
     * @return array<string, mixed>
     */
    public static function partialRules(mixed ...$arguments): array
    {
        return array_map(
            static fn (mixed $rule) => is_string($rule)
                ? str_replace('required|', 'nullable|', $rule)
                : $rule,
            static::rules(...$arguments),
        );
    }
}
