<?php

declare(strict_types=1);

namespace Sambat\NepaliCalendar;

use DateTimeImmutable;
use DateTimeZone;
use Sambat\NepaliCalendar\Constants\CalendarData;
use Sambat\NepaliCalendar\Exceptions\InvalidNepaliDateException;
use Sambat\NepaliCalendar\Exceptions\NepaliDateOutOfRangeException;

/**
 * The conversion engine. Both calendars are mapped onto a continuous
 * "epoch day" axis (day 0 == BS 2000-01-01 == AD 1943-04-14) using
 * precomputed cumulative day counts, so conversions are effectively O(1)
 * instead of the day-by-day loops used by most Nepali date packages.
 */
final class Calendar
{
    /** Days between BS 2000-01-01 and the start of each BS year. */
    private static ?array $yearStartDays = null;

    /** Total days in each BS year. */
    private static ?array $yearDays = null;

    /** Total days in the whole supported range (BS 2000 .. BS 2099). */
    private static ?int $totalDays = null;

    private static function build(): void
    {
        if (self::$totalDays !== null) {
            return;
        }

        $yearDays = [];
        foreach (CalendarData::NEPALI_YEARS as $year => $days) {
            $yearDays[$year] = array_sum($days);
        }

        $start = [];
        $running = 0;
        foreach ($yearDays as $year => $days) {
            $start[$year] = $running;
            $running += $days;
        }

        self::$yearDays = $yearDays;
        self::$yearStartDays = $start;
        self::$totalDays = $running;
    }

    public static function daysInBsMonth(int $year, int $month): int
    {
        self::assertYearInRange($year);

        if ($month < 1 || $month > 12) {
            throw new InvalidNepaliDateException("Invalid month [{$month}]. Month must be between 1 and 12.");
        }

        return CalendarData::NEPALI_YEARS[$year][$month - 1];
    }

    public static function daysInBsYear(int $year): int
    {
        self::assertYearInRange($year);
        self::build();

        return self::$yearDays[$year];
    }

    public static function isBsLeapYear(int $year): bool
    {
        return self::daysInBsYear($year) === 366;
    }

    public static function isValidBsDate(int $year, int $month, int $day): bool
    {
        if ($year < CalendarData::BS_MIN_YEAR || $year > CalendarData::BS_MAX_YEAR) {
            return false;
        }

        if ($month < 1 || $month > 12) {
            return false;
        }

        return $day >= 1 && $day <= self::daysInBsMonth($year, $month);
    }

    public static function assertValidBsDate(int $year, int $month, int $day): void
    {
        if (! self::isValidBsDate($year, $month, $day)) {
            throw NepaliDateOutOfRangeException::forBs($year, $month, $day);
        }
    }

    /**
     * Day-of-year (1-based) within its BS year.
     */
    public static function dayOfYear(int $year, int $month, int $day): int
    {
        self::assertValidBsDate($year, $month, $day);

        $dayOfYear = $day;
        for ($m = 1; $m < $month; $m++) {
            $dayOfYear += CalendarData::NEPALI_YEARS[$year][$m - 1];
        }

        return $dayOfYear;
    }

    /**
     * Days since BS 2000-01-01 (inclusive counting, so BS 2000-01-01 is 0).
     */
    public static function toEpochDay(int $year, int $month, int $day): int
    {
        self::assertValidBsDate($year, $month, $day);
        self::build();

        $epoch = self::$yearStartDays[$year];
        for ($m = 1; $m < $month; $m++) {
            $epoch += CalendarData::NEPALI_YEARS[$year][$m - 1];
        }

        return $epoch + $day - 1;
    }

    /**
     * @return array{year: int, month: int, day: int}
     */
    public static function fromEpochDay(int $epochDay): array
    {
        self::build();

        if ($epochDay < 0 || $epochDay >= self::$totalDays) {
            throw new NepaliDateOutOfRangeException(
                "Epoch day [{$epochDay}] is outside the supported range (0 .. ".(self::$totalDays - 1).').'
            );
        }

        $year = null;
        foreach (self::$yearStartDays as $y => $start) {
            if ($epochDay >= $start && $epochDay < $start + self::$yearDays[$y]) {
                $year = $y;
                break;
            }
        }

        if ($year === null) {
            throw new NepaliDateOutOfRangeException('Unable to resolve epoch day.');
        }

        $remaining = $epochDay - self::$yearStartDays[$year];
        $month = 1;
        foreach (CalendarData::NEPALI_YEARS[$year] as $monthDays) {
            if ($remaining < $monthDays) {
                break;
            }
            $remaining -= $monthDays;
            $month++;
        }

        return ['year' => $year, 'month' => $month, 'day' => $remaining + 1];
    }

    /**
     * Convert a Gregorian date to BS.
     *
     * @return array{year: int, month: int, day: int}
     */
    public static function adToBs(int $year, int $month, int $day): array
    {
        $ad = self::createAdDateTime($year, $month, $day);

        $epoch = self::adToEpochDay($ad);

        return self::fromEpochDay($epoch);
    }

    /**
     * Convert a BS date to Gregorian.
     *
     * @return array{year: int, month: int, day: int}
     */
    public static function bsToAd(int $year, int $month, int $day): array
    {
        $epoch = self::toEpochDay($year, $month, $day);
        self::build();

        $ad = (new DateTimeImmutable(CalendarData::BS_EPOCH_AD_DATE, new DateTimeZone('UTC')))
            ->setTime(0, 0)
            ->modify("+{$epoch} days");

        return [
            'year' => (int) $ad->format('Y'),
            'month' => (int) $ad->format('n'),
            'day' => (int) $ad->format('j'),
        ];
    }

    /**
     * Validate a Gregorian date and return its epoch-day offset.
     */
    private static function adToEpochDay(DateTimeImmutable $ad): int
    {
        self::build();

        $epoch = (new DateTimeImmutable(CalendarData::BS_EPOCH_AD_DATE, new DateTimeZone('UTC')))->setTime(0, 0);

        $diff = (int) floor(($ad->getTimestamp() - $epoch->getTimestamp()) / 86400);

        if ($diff < 0 || $diff >= self::$totalDays) {
            throw NepaliDateOutOfRangeException::forAd(
                (int) $ad->format('Y'),
                (int) $ad->format('n'),
                (int) $ad->format('j')
            );
        }

        return $diff;
    }

    /**
     * Strictly build a UTC midnight DateTimeImmutable from y/m/d,
     * rejecting impossible dates like 2025-02-31.
     */
    private static function createAdDateTime(int $year, int $month, int $day): DateTimeImmutable
    {
        $date = DateTimeImmutable::createFromFormat(
            '!Y-m-d',
            sprintf('%04d-%02d-%02d', $year, $month, $day),
            new DateTimeZone('UTC')
        );

        if ($date === false) {
            throw new InvalidNepaliDateException("Invalid Gregorian date: {$year}-{$month}-{$day}.");
        }

        return $date;
    }

    public static function isInAdRange(int $year, int $month, int $day): bool
    {
        try {
            self::adToBs($year, $month, $day);

            return true;
        } catch (InvalidNepaliDateException) {
            return false;
        }
    }

    /**
     * @return array{year: int, month: int, day: int} Inclusive AD range supported.
     */
    public static function adRange(): array
    {
        self::build();

        return [
            'min' => self::bsToAd(CalendarData::BS_MIN_YEAR, 1, 1),
            'max' => self::bsToAd(
                CalendarData::BS_MAX_YEAR,
                12,
                CalendarData::NEPALI_YEARS[CalendarData::BS_MAX_YEAR][11]
            ),
        ];
    }

    public static function totalDays(): int
    {
        self::build();

        return self::$totalDays;
    }

    /**
     * @return array{min: int, max: int}
     */
    public static function supportedYears(): array
    {
        return ['min' => CalendarData::BS_MIN_YEAR, 'max' => CalendarData::BS_MAX_YEAR];
    }

    private static function assertYearInRange(int $year): void
    {
        if ($year < CalendarData::BS_MIN_YEAR || $year > CalendarData::BS_MAX_YEAR) {
            throw new NepaliDateOutOfRangeException(
                "Year [{$year}] is outside the supported range "
                .'(BS '.CalendarData::BS_MIN_YEAR.' to '.CalendarData::BS_MAX_YEAR.').'
            );
        }
    }
}
