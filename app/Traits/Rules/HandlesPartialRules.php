<?php

namespace App\Traits\Rules;

trait HandlesPartialRules
{
    /**
     * Same as `rules()`, but required fields may be omitted. When sent, they still cannot be null.
     *
     * @return array<string, mixed>
     */
    public static function partialRules(mixed ...$arguments): array
    {
        return array_map(
            static fn (mixed $rule) => is_string($rule)
                ? str_replace('required|', 'sometimes|required|', $rule)
                : $rule,
            static::rules(...$arguments),
        );
    }
}
