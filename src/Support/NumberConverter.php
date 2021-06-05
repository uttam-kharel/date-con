<?php

declare(strict_types=1);

namespace Sambat\NepaliCalendar\Support;

/**
 * Converts between western (0-9) and Devanagari (०-९) numerals.
 */
final class NumberConverter
{
    private const DEVANAGARI = [
        '0' => '०',
        '1' => '१',
        '2' => '२',
        '3' => '३',
        '4' => '४',
        '5' => '५',
        '6' => '६',
        '7' => '७',
        '8' => '८',
        '9' => '९',
    ];

    public static function toNepali(string|int|float $value): string
    {
        return strtr((string) $value, self::DEVANAGARI);
    }

    public static function toEnglish(string|int|float $value): string
    {
        return strtr((string) $value, array_flip(self::DEVANAGARI));
    }

    public static function containsNepaliNumerals(string $value): bool
    {
        return preg_match('/[०-९]/u', $value) === 1;
    }
}
