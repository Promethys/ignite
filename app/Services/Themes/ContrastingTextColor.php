<?php

namespace App\Services\Themes;

class ContrastingTextColor
{
    public const LIGHT = '#ffffff';

    public const DARK = '#111111';

    private const LUMINANCE_THRESHOLD = 0.179;

    /**
     * Pick the text colour that stays readable on the given hex background.
     */
    public static function for(string $hexColor): string
    {
        return self::relativeLuminance($hexColor) > self::LUMINANCE_THRESHOLD ? self::DARK : self::LIGHT;
    }

    private static function relativeLuminance(string $hexColor): float
    {
        [$red, $green, $blue] = array_map(
            fn (string $channel): float => self::linearChannel(hexdec($channel) / 255),
            str_split(ltrim($hexColor, '#'), 2),
        );

        return 0.2126 * $red + 0.7152 * $green + 0.0722 * $blue;
    }

    private static function linearChannel(float $channel): float
    {
        return $channel <= 0.04045 ? $channel / 12.92 : (($channel + 0.055) / 1.055) ** 2.4;
    }
}
