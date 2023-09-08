<?php

use Sambat\NepaliCalendar\Calendar;
use Sambat\NepaliCalendar\NepaliDate;
use Sambat\NepaliCalendar\Support\NumberConverter;

if (! function_exists('nepali_date')) {
    /**
     * Parse a value into a NepaliDate (null = now).
     */
    function nepali_date(mixed $value = null): NepaliDate
    {
        return $value === null ? NepaliDate::now() : NepaliDate::parse($value);
    }
}

if (! function_exists('ad_to_bs')) {
    /**
     * Convert a Gregorian value to a formatted BS date.
     */
    function ad_to_bs(mixed $date, string $format = 'Y-m-d'): string
    {
        return NepaliDate::fromAd($date)->format($format);
    }
}

if (! function_exists('bs_to_ad')) {
    /**
     * Convert a BS date value to a formatted Gregorian date.
     */
    function bs_to_ad(mixed $date, string $format = 'Y-m-d'): string
    {
        return NepaliDate::parse($date)->formatAd($format);
    }
}

if (! function_exists('bs_today')) {
    /**
     * Today's date in the BS calendar.
     */
    function bs_today(string $format = 'Y-m-d'): string
    {
        return NepaliDate::now()->format($format);
    }
}

if (! function_exists('nepali_number')) {
    /**
     * Convert western digits to Devanagari: 2081 -> २०८१.
     */
    function nepali_number(string|int|float $value): string
    {
        return NumberConverter::toNepali($value);
    }
}

if (! function_exists('english_number')) {
    /**
     * Convert Devanagari digits to western: २०८१ -> 2081.
     */
    function english_number(string|int|float $value): string
    {
        return NumberConverter::toEnglish($value);
    }
}

if (! function_exists('bs_diff_for_humans')) {
    /**
     * Human friendly difference between two BS dates (defaults to now).
     */
    function bs_diff_for_humans(mixed $from, mixed $to = null): string
    {
        $from = NepaliDate::parse($from);

        return $from->diffForHumans($to === null ? null : NepaliDate::parse($to));
    }
}

if (! function_exists('bs_days_in_month')) {
    function bs_days_in_month(int $year, int $month): int
    {
        return Calendar::daysInBsMonth($year, $month);
    }
}

if (! function_exists('bs_days_in_year')) {
    function bs_days_in_year(int $year): int
    {
        return Calendar::daysInBsYear($year);
    }
}

if (! function_exists('bs_is_leap_year')) {
    function bs_is_leap_year(int $year): bool
    {
        return Calendar::isBsLeapYear($year);
    }
}

if (! function_exists('bs_age')) {
    /**
     * Full years elapsed since a BS birth date.
     */
    function bs_age(mixed $birthDate): int
    {
        return NepaliDate::parse($birthDate)->age();
    }
}
