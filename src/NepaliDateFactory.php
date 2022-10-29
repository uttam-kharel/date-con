<?php

declare(strict_types=1);

namespace Sambat\NepaliCalendar;

use Carbon\CarbonInterface;
use Sambat\NepaliCalendar\Constants\CalendarData;
use Sambat\NepaliCalendar\Enums\CalendarLanguage;
use Sambat\NepaliCalendar\Support\Config;

/**
 * Resolved by the NepaliDate facade. Mirrors the most common static entry
 * points so they can be called fluently from anywhere in a Laravel app.
 */
final class NepaliDateFactory
{
    public function now(): NepaliDate
    {
        return NepaliDate::now();
    }

    public function today(): NepaliDate
    {
        return NepaliDate::today();
    }

    public function parse(mixed $value): NepaliDate
    {
        return NepaliDate::parse($value);
    }

    public function fromAd(mixed $value): NepaliDate
    {
        return NepaliDate::fromAd($value);
    }

    public function fromBs(int $year, int $month, int $day, ?CarbonInterface $time = null): NepaliDate
    {
        return NepaliDate::fromBs($year, $month, $day, $time);
    }

    public function fromCarbon(CarbonInterface $carbon): NepaliDate
    {
        return NepaliDate::fromCarbon($carbon);
    }

    public function fromTimestamp(int $timestamp): NepaliDate
    {
        return NepaliDate::fromTimestamp($timestamp);
    }

    public function isValid(mixed $value): bool
    {
        return NepaliDate::isValid($value);
    }

    public function daysInMonth(int $year, int $month): int
    {
        return Calendar::daysInBsMonth($year, $month);
    }

    public function daysInYear(int $year): int
    {
        return Calendar::daysInBsYear($year);
    }

    public function isLeapYear(int $year): bool
    {
        return Calendar::isBsLeapYear($year);
    }

    /**
     * @return array<int, string> Month names in the given language.
     */
    public function monthNames(?string $language = null): array
    {
        $language = CalendarLanguage::resolve($language ?? Config::get('nepali-calendar.language', 'nepali'));

        return match ($language) {
            CalendarLanguage::Roman => CalendarData::MONTHS_ROMAN,
            CalendarLanguage::English => CalendarData::MONTHS_ENGLISH,
            CalendarLanguage::Nepali => CalendarData::MONTHS_DEVANAGARI,
        };
    }

    /**
     * @return array<int, string> Week day names (1 = Sunday .. 7 = Saturday).
     */
    public function weekDayNames(?string $language = null): array
    {
        $language = CalendarLanguage::resolve($language ?? Config::get('nepali-calendar.language', 'nepali'));

        return match ($language) {
            CalendarLanguage::Roman => CalendarData::WEEK_DAYS_ROMAN,
            CalendarLanguage::English => CalendarData::WEEK_DAYS_ENGLISH,
            CalendarLanguage::Nepali => CalendarData::WEEK_DAYS_DEVANAGARI,
        };
    }

    /**
     * @return array{bs: array{min: string, max: string}, ad: array{min: string, max: string}}
     */
    public function range(): array
    {
        $ad = Calendar::adRange();

        return [
            'bs' => [
                'min' => Calendar::fromEpochDay(0)['year'].'-01-01',
                'max' => Calendar::fromEpochDay(Calendar::totalDays() - 1)['year'].'-12-30',
            ],
            'ad' => [
                'min' => sprintf('%04d-%02d-%02d', $ad['min']['year'], $ad['min']['month'], $ad['min']['day']),
                'max' => sprintf('%04d-%02d-%02d', $ad['max']['year'], $ad['max']['month'], $ad['max']['day']),
            ],
        ];
    }

    /**
     * Quick conversion utility.
     *
     * @param  string  $from  'ad' or 'bs'
     */
    public function convert(string $date, string $from = 'ad', string $format = 'Y-m-d'): string
    {
        return match (strtolower($from)) {
            'ad', 'english', 'en' => NepaliDate::fromAd($date)->format($format),
            'bs', 'nep', 'nepali', 'np' => NepaliDate::parse($date)->format($format),
            default => throw new \InvalidArgumentException("Unknown calendar [{$from}]. Use 'ad' or 'bs'."),
        };
    }
}
