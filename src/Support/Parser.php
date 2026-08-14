<?php

declare(strict_types=1);

namespace Sambat\NepaliCalendar\Support;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use DateTimeInterface;
use Sambat\NepaliCalendar\Constants\CalendarData;
use Sambat\NepaliCalendar\Exceptions\InvalidNepaliDateException;
use Sambat\NepaliCalendar\NepaliDate;

/**
 * Parses many different input shapes into a NepaliDate.
 */
final class Parser
{
    public static function parse(mixed $value): NepaliDate
    {
        if ($value instanceof NepaliDate) {
            return $value;
        }

        if ($value instanceof CarbonInterface) {
            return NepaliDate::fromCarbon($value);
        }

        if ($value instanceof DateTimeInterface) {
            return NepaliDate::fromCarbon(Carbon::instance($value));
        }

        if (is_int($value)) {
            return NepaliDate::fromTimestamp($value);
        }

        if (is_array($value)) {
            return NepaliDate::fromArray($value);
        }

        if (is_string($value)) {
            return self::fromString($value);
        }

        throw InvalidNepaliDateException::forValue($value);
    }

    private static function fromString(string $value): NepaliDate
    {
        $value = trim($value);

        if ($value === '') {
            throw InvalidNepaliDateException::forValue($value);
        }

        // Relative words: "भोलि", "हिजो", "tomorrow", "next week" ...
        $relative = self::relativeDate($value);
        if ($relative !== null) {
            return $relative;
        }

        // Accept Devanagari numerals transparently: "२०८१-११-०५"
        if (NumberConverter::containsNepaliNumerals($value)) {
            $value = NumberConverter::toEnglish($value);
        }

        // Compact form: 20811128
        if (preg_match('/^\d{8}$/', $value) === 1) {
            return NepaliDate::fromBs(
                (int) substr($value, 0, 4),
                (int) substr($value, 4, 2),
                (int) substr($value, 6, 2)
            );
        }

        // Numeric with separators, optionally carrying a time: "2081-11-28 14:30:00"
        if (preg_match('#^(\d{3,4})\s*[-/\\.\s]\s*(\d{1,2})\s*[-/\\.\s]\s*(\d{1,2})(?:\s+(\d{1,2}):(\d{2})(?::(\d{2}))?)?$#', $value, $matches) === 1) {
            $time = null;
            if (isset($matches[4])) {
                $time = Carbon::createFromTime((int) $matches[4], (int) ($matches[5] ?? 0), (int) ($matches[6] ?? 0));
            }

            return NepaliDate::fromBs((int) $matches[1], (int) $matches[2], (int) $matches[3], $time);
        }

        // Month name form, Devanagari: "2081 फागुन 28" or "2081-फागुन-28"
        if (preg_match('#^(\d{3,4})\s*[-/\\.\s]\s*([\x{0900}-\x{097F}]+)\s*[-/\\.\s]\s*(\d{1,2})$#u', $value, $matches) === 1) {
            $month = self::monthFromDevanagari($matches[2]);

            return NepaliDate::fromBs((int) $matches[1], $month, (int) $matches[3]);
        }

        // Month name form, romanized: "2081 Falgun 28" or "2081-02 Falgun 28"
        if (preg_match('#^(\d{3,4})\s*[-/\\.\s]\s*([a-zA-Z]+)\s*[-/\\.\s]\s*(\d{1,2})$#i', $value, $matches) === 1) {
            $month = self::monthFromRoman($matches[2]);

            return NepaliDate::fromBs((int) $matches[1], $month, (int) $matches[3]);
        }

        throw InvalidNepaliDateException::forValue($value);
    }

    /**
     * Resolve relative day/week/month words to a concrete date.
     *
     * Nepali: आज (today), हिजो (yesterday), अस्ति (2 days ago), भोलि
     * (tomorrow), पर्सि (2 days ahead), परसि (3 days ahead). English:
     * today / tomorrow / yesterday / next- and last-week|month|year.
     */
    private static function relativeDate(string $value): ?NepaliDate
    {
        $now = NepaliDate::now();

        return match (mb_strtolower($value)) {
            'today', 'आज', 'अहिले' => $now,
            'tomorrow', 'भोलि' => $now->addDay(),
            'पर्सि' => $now->addDays(2),
            'परसि' => $now->addDays(3),
            'yesterday', 'हिजो' => $now->subDay(),
            'अस्ति' => $now->subDays(2),
            'next week' => $now->addWeeks(1),
            'last week' => $now->subWeeks(1),
            'next month' => $now->addMonths(1),
            'last month' => $now->subMonths(1),
            'next year' => $now->addYears(1),
            'last year' => $now->subYears(1),
            default => null,
        };
    }

    private static function monthFromDevanagari(string $name): int
    {
        foreach ([CalendarData::MONTHS_DEVANAGARI, CalendarData::MONTHS_DEVANAGARI_FORMAL] as $list) {
            foreach ($list as $month => $candidate) {
                if ($candidate === $name) {
                    return $month;
                }
            }
        }

        throw InvalidNepaliDateException::forValue($name);
    }

    private static function monthFromRoman(string $name): int
    {
        $normalized = strtolower($name);

        foreach (CalendarData::MONTHS_ROMAN as $month => $candidate) {
            if (strtolower($candidate) === $normalized) {
                return $month;
            }
        }

        // Accept unambiguous abbreviations (first 3 letters).
        $short = substr($normalized, 0, 3);
        $match = null;
        foreach (CalendarData::MONTHS_ROMAN as $month => $candidate) {
            if (substr(strtolower($candidate), 0, 3) === $short) {
                if ($match !== null) {
                    // Ambiguous abbreviation (e.g. "ash").
                    throw InvalidNepaliDateException::forValue($name);
                }
                $match = $month;
            }
        }

        if ($match !== null) {
            return $match;
        }

        throw InvalidNepaliDateException::forValue($name);
    }
}
