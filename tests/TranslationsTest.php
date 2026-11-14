<?php

use PHPUnit\Framework\TestCase;

final class TranslationsTest extends TestCase
{
    public function test_all_five_languages_have_the_same_keys_and_nonempty_values(): void
    {
        $base = require dirname(__DIR__).'/lang/en/ui.php';
        $flatten = function (array $items, string $prefix = '') use (&$flatten): array {
            $result = [];
            foreach ($items as $key => $value) {
                if (is_array($value)) {
                    $result += $flatten($value, $prefix.$key.'.');
                } else {
                    $result[$prefix.$key] = $value;
                }
            }
            ksort($result);

            return $result;
        };
        $base = $flatten($base);
        foreach (['en', 'zh_CN', 'zh_TW', 'ja', 'ko'] as $locale) {
            $values = $flatten(require dirname(__DIR__).'/lang/'.$locale.'/ui.php');
            self::assertSame(array_keys($base), array_keys($values), $locale);
            foreach ($values as $value) {
                self::assertIsString($value);
                self::assertNotSame('', trim($value));
            }
        }
    }
}
