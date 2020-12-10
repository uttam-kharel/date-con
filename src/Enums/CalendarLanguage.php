<?php

declare(strict_types=1);

namespace Sambat\NepaliCalendar\Enums;

/**
 * Language used for month / weekday names when formatting.
 */
enum CalendarLanguage: string
{
    /** Devanagari script (default): वैशाख, बुधबार ... */
    case Nepali = 'nepali';

    /** Latin transliteration: Baisakh, Budhabaar ... */
    case Roman = 'roman';

    /** Gregorian names of the equivalent AD date: March, Wednesday ... */
    case English = 'english';

    /**
     * Resolve a config value / user input into a language, tolerating
     * common aliases and falling back to Nepali.
     */
    public static function resolve(?string $language): self
    {
        return match (strtolower((string) $language)) {
            'roman', 'romanized', 'latin', 'en-roman' => self::Roman,
            'english', 'en', 'ad' => self::English,
            default => self::Nepali,
        };
    }
}
