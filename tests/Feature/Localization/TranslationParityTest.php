<?php

namespace Tests\Feature\Localization;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TranslationParityTest extends TestCase
{
    /** @return array<int, array{string}> */
    public static function langFileProvider(): array
    {
        $langPath = dirname(__DIR__, 3).'/lang/en';

        return array_map(
            fn ($file) => [basename($file, '.php')],
            glob($langPath.'/*.php'),
        );
    }

    #[DataProvider('langFileProvider')]
    public function test_every_en_key_exists_in_fr(string $file): void
    {
        $enKeys = $this->flattenKeys(require lang_path("en/{$file}.php"));
        $frKeys = $this->flattenKeys(require lang_path("fr/{$file}.php"));

        $missing = array_diff($enKeys, $frKeys);

        $this->assertEmpty(
            $missing,
            "lang/fr/{$file}.php is missing keys present in EN: ".implode(', ', $missing),
        );
    }

    #[DataProvider('langFileProvider')]
    public function test_every_fr_key_exists_in_en(string $file): void
    {
        $enKeys = $this->flattenKeys(require lang_path("en/{$file}.php"));
        $frKeys = $this->flattenKeys(require lang_path("fr/{$file}.php"));

        $missing = array_diff($frKeys, $enKeys);

        $this->assertEmpty(
            $missing,
            "lang/en/{$file}.php is missing keys present in FR: ".implode(', ', $missing),
        );
    }

    /**
     * Recursively flatten a nested array into dotted keys.
     *
     * @param  array<string, mixed>  $array
     * @return list<string>
     */
    protected function flattenKeys(array $array, string $prefix = ''): array
    {
        $keys = [];

        foreach ($array as $key => $value) {
            $fullKey = $prefix === '' ? (string) $key : "{$prefix}.{$key}";

            if (is_array($value)) {
                $keys = array_merge($keys, $this->flattenKeys($value, $fullKey));
            } else {
                $keys[] = $fullKey;
            }
        }

        return $keys;
    }
}
