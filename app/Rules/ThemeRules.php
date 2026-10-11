<?php

namespace App\Rules;

use App\Services\Themes\ThemeStylesheet;

/**
 * Theme validation rules shared by the store and update requests.
 */
class ThemeRules
{
    public const NAME_MAX_LENGTH = 50;

    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:'.self::NAME_MAX_LENGTH],
            'light_color' => ['required', 'string', 'regex:'.ThemeStylesheet::HEX_COLOR_PATTERN],
            'dark_color' => ['nullable', 'string', 'regex:'.ThemeStylesheet::HEX_COLOR_PATTERN],
        ];
    }
}
