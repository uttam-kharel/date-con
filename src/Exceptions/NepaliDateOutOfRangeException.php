<?php

declare(strict_types=1);

namespace Sambat\NepaliCalendar\Exceptions;

use Sambat\NepaliCalendar\Calendar;
use Sambat\NepaliCalendar\Constants\CalendarData;

/**
 * Thrown when a conversion or arithmetic result falls outside the
 * supported calendar range (BS 2000-01-01 .. BS 2100-12-30). The labels
 * in the messages are derived from the active dataset, so they stay
 * correct as the calendar data grows.
 */
class NepaliDateOutOfRangeException extends InvalidNepaliDateException
{
    public static function forBs(int $year, int $month, int $day): self
    {
        return new self(
            "The Nepali date {$year}-{$month}-{$day} is outside the supported range "
            .self::bsRangeLabel().'.'
        );
    }

    public static function forAd(int $year, int $month, int $day): self
    {
        return new self(
            "The Gregorian date {$year}-{$month}-{$day} is outside the supported range "
            .self::adRangeLabel().'.'
        );
    }

    private static function bsRangeLabel(): string
    {
        $max = CalendarData::BS_MAX_YEAR;

        return sprintf(
            '(BS %04d-01-01 to BS %04d-12-%d)',
            CalendarData::BS_MIN_YEAR,
            $max,
            CalendarData::NEPALI_YEARS[$max][11]
        );
    }

    private static function adRangeLabel(): string
    {
        try {
            $range = Calendar::adRange();
            $min = $range['min'];
            $max = $range['max'];

            return sprintf(
                '(AD %04d-%02d-%02d to AD %04d-%02d-%02d)',
                $min['year'], $min['month'], $min['day'],
                $max['year'], $max['month'], $max['day']
            );
        } catch (\Throwable) {
            // Fallback if the provider itself cannot resolve.
            return '(AD 1943-04-14 to AD 2044-04-12)';
        }
    }
}
