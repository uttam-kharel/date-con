<?php

declare(strict_types=1);

namespace Sambat\NepaliCalendar\Support;

use Sambat\NepaliCalendar\Constants\CalendarData;
use Sambat\NepaliCalendar\Enums\CalendarLanguage;
use Sambat\NepaliCalendar\NepaliDate;

/**
 * Carbon-style format engine for BS dates.
 *
 * Supports the PHP date() tokens (plus BS-specific meaning for F, M, l, D,
 * z, t, L, W) with optional Devanagari numerals and Nepali/Romanized names.
 * Escape a literal character with a backslash: "Y \y\e\a\r".
 *
 * Time tokens (g G h H i s A a U e O P T c r) are resolved from the
 * equivalent Gregorian instant.
 */
final class Formatter
{
    /**
     * @var array<string, string> tokens that are delegated to the AD Carbon
     */
    private const AD_TOKENS = ['g', 'G', 'h', 'H', 'i', 's', 'A', 'a', 'U', 'e', 'O', 'P', 'T', 'c', 'r'];

    public static function format(
        NepaliDate $date,
        string $format,
        ?string $language = null,
        ?bool $devanagariNumerals = null
    ): string {
        $language = CalendarLanguage::resolve($language ?? Config::get('nepali-calendar.language', 'nepali'));
        $devanagari = $devanagariNumerals ?? (Config::get('nepali-calendar.numerals', 'devanagari') === 'devanagari');

        // In English mode the whole date is rendered on the Gregorian calendar.
        if ($language === CalendarLanguage::English) {
            $formatted = $date->ad()->format($format);

            return $devanagari ? NumberConverter::toNepali($formatted) : $formatted;
        }

        $output = '';
        $length = strlen($format);

        for ($i = 0; $i < $length; $i++) {
            $char = $format[$i];

            if ($char === '\\') {
                $output .= $i + 1 < $length ? $format[++$i] : '';

                continue;
            }

            $output .= self::token($date, $char, $language);
        }

        return $devanagari ? NumberConverter::toNepali($output) : $output;
    }

    private static function token(NepaliDate $date, string $token, CalendarLanguage $language): string
    {
        return match ($token) {
            'Y' => (string) $date->year(),
            'y' => str_pad((string) ($date->year() % 100), 2, '0', STR_PAD_LEFT),
            'm' => str_pad((string) $date->month(), 2, '0', STR_PAD_LEFT),
            'n' => (string) $date->month(),
            'M' => self::monthShortName($date, $language),
            'F' => self::monthName($date, $language),
            'd' => str_pad((string) $date->day(), 2, '0', STR_PAD_LEFT),
            'j' => (string) $date->day(),
            'S' => self::ordinalSuffix($date->day()),
            'D' => self::weekDayShort($date, $language),
            'l' => self::weekDayName($date, $language),
            'N' => (string) $date->weekDayIso(),
            'w' => (string) ($date->weekDay() - 1),
            'z' => (string) ($date->dayOfYear() - 1),
            't' => (string) $date->daysInMonth(),
            'L' => $date->isLeapYear() ? '1' : '0',
            'W' => (string) $date->weekOfYear(),
            default => in_array($token, self::AD_TOKENS, true) ? $date->ad()->format($token) : $token,
        };
    }

    /* ------------------------------------------------------------------
     | Public name helpers (used by NepaliDate accessors)
     | ------------------------------------------------------------------ */

    public static function monthNamePublic(NepaliDate $date, ?string $language = null): string
    {
        return self::monthName($date, CalendarLanguage::resolve($language ?? Config::get('nepali-calendar.language', 'nepali')));
    }

    public static function monthShortNamePublic(NepaliDate $date, ?string $language = null): string
    {
        return self::monthShortName($date, CalendarLanguage::resolve($language ?? Config::get('nepali-calendar.language', 'nepali')));
    }

    public static function weekDayNamePublic(NepaliDate $date, ?string $language = null): string
    {
        return self::weekDayName($date, CalendarLanguage::resolve($language ?? Config::get('nepali-calendar.language', 'nepali')));
    }

    public static function weekDayShortPublic(NepaliDate $date, ?string $language = null): string
    {
        return self::weekDayShort($date, CalendarLanguage::resolve($language ?? Config::get('nepali-calendar.language', 'nepali')));
    }

    private static function monthName(NepaliDate $date, CalendarLanguage $language): string
    {
        return match ($language) {
            CalendarLanguage::Nepali => CalendarData::MONTHS_DEVANAGARI[$date->month()],
            CalendarLanguage::Roman => CalendarData::MONTHS_ROMAN[$date->month()],
            CalendarLanguage::English => $date->ad()->format('F'),
        };
    }

    private static function monthShortName(NepaliDate $date, CalendarLanguage $language): string
    {
        return match ($language) {
            CalendarLanguage::Nepali => CalendarData::MONTHS_DEVANAGARI[$date->month()],
            CalendarLanguage::Roman => substr(CalendarData::MONTHS_ROMAN[$date->month()], 0, 3),
            CalendarLanguage::English => $date->ad()->format('M'),
        };
    }

    private static function weekDayName(NepaliDate $date, CalendarLanguage $language): string
    {
        return match ($language) {
            CalendarLanguage::Nepali => CalendarData::WEEK_DAYS_DEVANAGARI[$date->weekDay()],
            CalendarLanguage::Roman => CalendarData::WEEK_DAYS_ROMAN[$date->weekDay()],
            CalendarLanguage::English => $date->ad()->format('l'),
        };
    }

    private static function weekDayShort(NepaliDate $date, CalendarLanguage $language): string
    {
        return match ($language) {
            CalendarLanguage::Nepali => CalendarData::WEEK_DAYS_DEVANAGARI[$date->weekDay()],
            CalendarLanguage::Roman => substr(CalendarData::WEEK_DAYS_ROMAN[$date->weekDay()], 0, 3),
            CalendarLanguage::English => $date->ad()->format('D'),
        };
    }

    private static function ordinalSuffix(int $day): string
    {
        if ($day % 100 >= 11 && $day % 100 <= 13) {
            return 'th';
        }

        return match ($day % 10) {
            1 => 'st',
            2 => 'nd',
            3 => 'rd',
            default => 'th',
        };
    }
}
