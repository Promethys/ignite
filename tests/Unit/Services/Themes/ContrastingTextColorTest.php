<?php

namespace Tests\Unit\Services\Themes;

use App\Services\Themes\ContrastingTextColor;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ContrastingTextColorTest extends TestCase
{
    #[DataProvider('backgrounds')]
    public function test_it_picks_the_readable_text_color(string $background, string $expected): void
    {
        $this->assertSame($expected, ContrastingTextColor::for($background));
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function backgrounds(): array
    {
        return [
            'black' => ['#000000', ContrastingTextColor::LIGHT],
            'white' => ['#ffffff', ContrastingTextColor::DARK],
            'default violet' => ['#7c3bed', ContrastingTextColor::LIGHT],
            'deep blue' => ['#2563eb', ContrastingTextColor::LIGHT],
            'pale yellow' => ['#fde047', ContrastingTextColor::DARK],
            'orange' => ['#f97316', ContrastingTextColor::DARK],
            'uppercase hex' => ['#FDE047', ContrastingTextColor::DARK],
        ];
    }
}
