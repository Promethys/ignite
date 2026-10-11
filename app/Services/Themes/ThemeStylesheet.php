<?php

namespace App\Services\Themes;

use Illuminate\Http\Request;

class ThemeStylesheet
{
    public const HEX_COLOR_PATTERN = '/^#[0-9a-fA-F]{6}$/';

    private const UNTHEMED_ROUTES = [
        'home',
        'login',
        'register',
        'password.request',
        'password.reset',
        'password.confirm',
        'verification.notice',
        'two-factor.login',
    ];

    /**
     * The CSS that applies the user's active theme to the current page, if any.
     */
    public function forRequest(Request $request): ?string
    {
        $user = $request->user();

        if ($user === null || $request->routeIs(...self::UNTHEMED_ROUTES)) {
            return null;
        }

        $colors = $user->themes()->where('is_active', true)->toBase()->first(['light_color', 'dark_color']);

        return $colors === null ? null : $this->build($colors->light_color, $colors->dark_color);
    }

    /**
     * The CSS that sets the primary colour and its text colour for each mode.
     */
    public function build(string $lightColor, ?string $darkColor): ?string
    {
        $darkColor ??= $lightColor;

        if (! preg_match(self::HEX_COLOR_PATTERN, $lightColor) || ! preg_match(self::HEX_COLOR_PATTERN, $darkColor)) {
            return null;
        }

        return sprintf(
            'html:root{--primary:%s;--primary-foreground:%s}html.dark{--primary:%s;--primary-foreground:%s}',
            $lightColor,
            ContrastingTextColor::for($lightColor),
            $darkColor,
            ContrastingTextColor::for($darkColor),
        );
    }
}
