<?php

declare(strict_types=1);

namespace Sambat\NepaliCalendar;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use DateTimeInterface;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use JsonSerializable;
use Sambat\NepaliCalendar\Constants\CalendarData;
use Sambat\NepaliCalendar\Enums\DateFormat;
use Sambat\NepaliCalendar\Enums\Rashi;
use Sambat\NepaliCalendar\Enums\Season;
use Sambat\NepaliCalendar\Exceptions\InvalidNepaliDateException;
use Sambat\NepaliCalendar\Holidays\HolidayRepository;
use Sambat\NepaliCalendar\Holidays\NepaliHoliday;
use Sambat\NepaliCalendar\Support\Config;
use Sambat\NepaliCalendar\Support\Formatter;
use Sambat\NepaliCalendar\Support\NumberConverter;
use Sambat\NepaliCalendar\Support\Parser;
use Stringable;

/**
 * An immutable Nepali (Bikram Sambat) date.
 *
 * Carbon-style fluent API: conversion, formatting with Devanagari numerals,
 * arithmetic, diffs, periods, comparisons and calendar grids. The object
 * always knows its equivalent Gregorian instant (->ad()).
 */
final class NepaliDate implements Arrayable, Jsonable, JsonSerializable, Stringable
{
    private readonly int $year;

    private readonly int $month;

    private readonly int $day;

    /** Equivalent Gregorian instant (date + optional wall time). */
    private readonly Carbon $ad;

    public function __construct(int $year, int $month, int $day, ?CarbonInterface $time = null)
    {
        Calendar::assertValidBsDate($year, $month, $day);

        $this->year = $year;
        $this->month = $month;
        $this->day = $day;

        $ad = Calendar::bsToAd($year, $month, $day);

        $this->ad = $time !== null
            ? Carbon::instance($time)->setDate($ad['year'], $ad['month'], $ad['day'])
            : Carbon::create($ad['year'], $ad['month'], $ad['day']);
    }

    /* ------------------------------------------------------------------
     | Factories
     | ------------------------------------------------------------------ */

    /** The current moment, converted to BS. */
    public static function now(): self
    {
        $now = Carbon::now();
        $bs = Calendar::adToBs(
            (int) $now->format('Y'),
            (int) $now->format('n'),
            (int) $now->format('j')
        );

        return new self($bs['year'], $bs['month'], $bs['day'], $now);
    }

    /** Today's BS date (time preserved, date-only semantics). */
    public static function today(): self
    {
        return self::now();
    }

    /**
     * Parse a value as a BS date. Accepts:
     *  - "2081-11-28", "2081/11/28", "2081.11.28", "20811128", "2081-11-28 14:30"
     *  - "2081 Falgun 28" / "2081-फागुन-28" (romanized or Devanagari names)
     *  - Devanagari numerals ("२०८१-११-०५")
     *  - arrays / Carbon / DateTime / timestamps (timestamps are AD instants)
     */
    public static function parse(mixed $value): self
    {
        return Parser::parse($value);
    }

    /** Build a BS date from explicit year, month and day. */
    public static function fromBs(int $year, int $month, int $day, ?CarbonInterface $time = null): self
    {
        return new self($year, $month, $day, $time);
    }

    /**
     * Build a BS date from a format string — the reverse of format().
     *
     * Interpreted tokens: Y (4-digit year), y (2-digit year), m/n (month),
     * d/j (day), F (full month name), M (short month name). F/M accept both
     * Devanagari (साउन) and romanized (Shrawan / Shr) names; numerals may be
     * Devanagari. All other characters are matched literally; prefix a
     * character with a backslash to force literal interpretation.
     */
    public static function createFromFormat(string $format, string $value): self
    {
        $value = trim($value);

        // Month-name lookup: name => BS month index (1-12).
        $names = [];
        foreach (CalendarData::MONTHS_DEVANAGARI as $month => $name) {
            $names[$name] = $month;
        }
        foreach (CalendarData::MONTHS_DEVANAGARI_FORMAL as $month => $name) {
            $names[$name] = $month;
        }
        foreach (CalendarData::MONTHS_ROMAN as $month => $name) {
            $names[$name] = $month;
            $names[substr($name, 0, 3)] = $month;
        }
        uksort($names, static fn (string $a, string $b): int => strlen($b) <=> strlen($a));

        $monthPattern = '(?:'.implode('|', array_map(static fn (string $name): string => preg_quote($name, '~'), array_keys($names))).')';

        $regex = '';
        $captures = []; // token => capture index (first occurrence wins)
        $length = strlen($format);

        for ($i = 0; $i < $length; $i++) {
            $char = $format[$i];

            if ($char === '\\') {
                $regex .= preg_quote($format[$i + 1] ?? '', '~');
                $i++;

                continue;
            }

            switch ($char) {
                case 'Y':
                    $regex .= '(\d{4})';
                    break;
                case 'y':
                    $regex .= '(\d{2})';
                    break;
                case 'm':
                case 'n':
                    $regex .= '(\d{1,2})';
                    break;
                case 'd':
                case 'j':
                    $regex .= '(\d{1,2})';
                    break;
                case 'F':
                case 'M':
                    $regex .= '('.$monthPattern.')';
                    break;
                default:
                    $regex .= preg_quote($char, '~');

                    continue 2;
            }

            $captures[$char] ??= count($captures) + 1;
        }

        // Devanagari digits in the value are transparently converted.
        if (NumberConverter::containsNepaliNumerals($value)) {
            $value = NumberConverter::toEnglish($value);
        }

        if (preg_match('~^'.$regex.'$~u', $value, $matches) !== 1) {
            throw InvalidNepaliDateException::forValue($value);
        }

        $year = $month = $day = null;

        foreach ($captures as $token => $index) {
            $raw = $matches[$index];

            match ($token) {
                'Y' => $year = (int) $raw,
                'y' => $year = 2000 + (int) $raw,
                'm', 'n' => $month = (int) $raw,
                'd', 'j' => $day = (int) $raw,
                'F', 'M' => $month = $names[$raw] ?? null,
                default => null,
            };
        }

        if ($year === null || $month === null || $day === null) {
            throw InvalidNepaliDateException::forValue($value);
        }

        return self::fromBs($year, $month, $day);
    }

    /**
     * Build a BS date from a Gregorian value (Carbon, DateTime, "2025-02-17",
     * timestamp or array).
     */
    public static function fromAd(mixed $value): self
    {
        if ($value instanceof CarbonInterface) {
            return self::fromCarbon($value);
        }

        if ($value instanceof DateTimeInterface) {
            return self::fromCarbon(Carbon::instance($value));
        }

        if (is_int($value)) {
            return self::fromTimestamp($value);
        }

        if (is_array($value)) {
            $year = $value['year'] ?? $value['y'] ?? $value[0] ?? null;
            $month = $value['month'] ?? $value['m'] ?? $value[1] ?? null;
            $day = $value['day'] ?? $value['d'] ?? $value[2] ?? null;

            if ($year === null || $month === null || $day === null) {
                throw InvalidNepaliDateException::forValue($value);
            }

            return self::fromCarbon(Carbon::create((int) $year, (int) $month, (int) $day));
        }

        if (is_string($value)) {
            $carbon = Carbon::parse($value);

            // Reject lenient overflow such as "2025-02-31" which Carbon would
            // silently roll over to 2025-03-03.
            $digits = preg_replace('/\D/', '', $value);
            if ($digits !== null && strlen($digits) >= 8 && ! str_starts_with($carbon->format('Ymd'), substr($digits, 0, 8))) {
                throw InvalidNepaliDateException::forValue($value);
            }

            return self::fromCarbon($carbon);
        }

        throw InvalidNepaliDateException::forValue($value);
    }

    public static function fromCarbon(CarbonInterface $carbon): self
    {
        $carbon = Carbon::instance($carbon);

        $bs = Calendar::adToBs(
            (int) $carbon->format('Y'),
            (int) $carbon->format('n'),
            (int) $carbon->format('j')
        );

        return new self($bs['year'], $bs['month'], $bs['day'], $carbon);
    }

    public static function fromTimestamp(int $timestamp): self
    {
        return self::fromCarbon(Carbon::createFromTimestamp($timestamp));
    }

    /**
     * Build from an array. Accepts ['year' => 2081, 'month' => 11, 'day' => 28],
     * [2081, 11, 28] or a toArray() result.
     */
    public static function fromArray(array $value): self
    {
        $year = $value['year'] ?? $value['y'] ?? ($value[0] ?? null);
        $month = $value['month'] ?? $value['m'] ?? ($value[1] ?? null);
        $day = $value['day'] ?? $value['d'] ?? ($value[2] ?? null);

        if ($year === null || $month === null || $day === null) {
            throw InvalidNepaliDateException::forValue($value);
        }

        return new self((int) $year, (int) $month, (int) $day);
    }

    /** Whether a value parses to a valid BS date. */
    public static function isValid(mixed $value): bool
    {
        try {
            self::parse($value);

            return true;
        } catch (InvalidNepaliDateException) {
            return false;
        }
    }

    public static function min(self ...$dates): self
    {
        return array_reduce($dates, fn (?self $carry, self $date) => $carry === null || $date->isBefore($carry) ? $date : $carry);
    }

    public static function max(self ...$dates): self
    {
        return array_reduce($dates, fn (?self $carry, self $date) => $carry === null || $date->isAfter($carry) ? $date : $carry);
    }

    public static function compare(self $a, self $b): int
    {
        return $a->compareTo($b);
    }

    /* ------------------------------------------------------------------
     | Accessors
     | ------------------------------------------------------------------ */

    public function year(): int
    {
        return $this->year;
    }

    public function month(): int
    {
        return $this->month;
    }

    public function day(): int
    {
        return $this->day;
    }

    /** Quarter of the BS year, 1-4 (Baisakh-Jeth = Q1). */
    public function quarter(): int
    {
        return intdiv($this->month - 1, 3) + 1;
    }

    /**
     * Fiscal quarter, 1-4. The Nepali fiscal year starts in Shrawan, so
     * Shrawan-Ashwin = Q1 ... Baisakh-Ashadh = Q4.
     */
    public function fiscalQuarter(): int
    {
        return intdiv((($this->month - NepaliFiscalYear::START_MONTH) % 12 + 12) % 12, 3) + 1;
    }

    /** The fiscal year this date belongs to (Shrawan 1 based). */
    public function fiscalYear(): NepaliFiscalYear
    {
        return NepaliFiscalYear::fromDate($this);
    }

    /** Week day 1 (Sunday) .. 7 (Saturday) — the Nepali convention. */
    public function weekDay(): int
    {
        return (int) $this->ad->format('w') + 1;
    }

    /** ISO week day 1 (Monday) .. 7 (Sunday). */
    public function weekDayIso(): int
    {
        return (int) $this->ad->format('N');
    }

    public function weekDayName(?string $language = null): string
    {
        return Formatter::weekDayNamePublic($this, $language);
    }

    public function weekDayShort(?string $language = null): string
    {
        return Formatter::weekDayShortPublic($this, $language);
    }

    public function monthName(?string $language = null): string
    {
        return Formatter::monthNamePublic($this, $language);
    }

    public function monthShortName(?string $language = null): string
    {
        return Formatter::monthShortNamePublic($this, $language);
    }

    /** The classical Nepali season (ऋतु) this date falls in. */
    public function season(): Season
    {
        return Season::fromMonth($this->month);
    }

    /** The Nepali zodiac sign (rashi) of the BS month. */
    public function rashi(): Rashi
    {
        return Rashi::fromMonth($this->month);
    }

    /** Day of the BS year, 1-based. */
    public function dayOfYear(): int
    {
        return Calendar::dayOfYear($this->year, $this->month, $this->day);
    }

    /** Week number within the BS year, 1-based (approximation of ISO style). */
    public function weekOfYear(): int
    {
        return intdiv($this->dayOfYear() - 1, 7) + 1;
    }

    public function daysInMonth(): int
    {
        return Calendar::daysInBsMonth($this->year, $this->month);
    }

    public function daysInYear(): int
    {
        return Calendar::daysInBsYear($this->year);
    }

    /** A BS year is "leap" when it contains 366 days. */
    public function isLeapYear(): bool
    {
        return Calendar::isBsLeapYear($this->year);
    }

    /** The equivalent Gregorian instant (Carbon). */
    public function ad(): Carbon
    {
        return $this->ad->copy();
    }

    public function timestamp(): int
    {
        return $this->ad->getTimestamp();
    }

    /* ------------------------------------------------------------------
     | Formatting
     | ------------------------------------------------------------------ */

    public function format(string $format, ?string $language = null, ?bool $devanagariNumerals = null): string
    {
        return Formatter::format($this, $format, $language, $devanagariNumerals);
    }

    /** Format using one of the named DateFormat presets. */
    public function formatPreset(DateFormat $preset, ?string $language = null, ?bool $devanagariNumerals = null): string
    {
        return $this->format($preset->value, $language, $devanagariNumerals);
    }

    /** Format using the Gregorian calendar (delegates to Carbon). */
    public function formatAd(string $format): string
    {
        return $this->ad->format($format);
    }

    /**
     * The same instant rendered in both calendars: the BS date with Nepali
     * names and Devanagari numerals, and the equivalent Gregorian date with
     * English names and western numerals.
     *
     * @return array{nepali: string, english: string}
     */
    public function formatBoth(string $format = 'l, F j, Y'): array
    {
        return [
            'nepali' => $this->format($format, 'nepali'),
            'english' => $this->format($format, 'english', false),
        ];
    }

    public function toDateString(string $separator = '-'): string
    {
        return sprintf('%04d%s%02d%s%02d', $this->year, $separator, $this->month, $separator, $this->day);
    }

    public function toEnglishNumerals(): string
    {
        return $this->toDateString();
    }

    public function toNepaliNumerals(): string
    {
        return NumberConverter::toNepali($this->toDateString());
    }

    public function __toString(): string
    {
        return $this->format((string) Config::get('nepali-calendar.default_format', 'Y-m-d'));
    }

    /* ------------------------------------------------------------------
     | Arithmetic (immutable — every method returns a new instance)
     | ------------------------------------------------------------------ */

    public function addDays(int $days): self
    {
        $epoch = Calendar::toEpochDay($this->year, $this->month, $this->day) + $days;
        $bs = Calendar::fromEpochDay($epoch);

        return new self($bs['year'], $bs['month'], $bs['day'], $this->ad);
    }

    public function subDays(int $days): self
    {
        return $this->addDays(-$days);
    }

    public function addWeeks(int $weeks): self
    {
        return $this->addDays($weeks * 7);
    }

    public function subWeeks(int $weeks): self
    {
        return $this->addWeeks(-$weeks);
    }

    /**
     * Add calendar months using BS-native arithmetic with overflow clamping:
     * e.g. Magh 30 + 1 month becomes Falgun 29 when Falgun has 29 days.
     */
    public function addMonths(int $months): self
    {
        $total = $this->year * 12 + ($this->month - 1) + $months;

        $year = (int) floor($total / 12);
        $month = (($total % 12) + 12) % 12 + 1;
        $day = min($this->day, Calendar::daysInBsMonth($year, $month));

        return new self($year, $month, $day, $this->ad);
    }

    public function subMonths(int $months): self
    {
        return $this->addMonths(-$months);
    }

    public function addYears(int $years): self
    {
        $year = $this->year + $years;
        $day = min($this->day, Calendar::daysInBsMonth($year, $this->month));

        return new self($year, $this->month, $day, $this->ad);
    }

    public function subYears(int $years): self
    {
        return $this->addYears(-$years);
    }

    public function addDay(): self
    {
        return $this->addDays(1);
    }

    public function subDay(): self
    {
        return $this->addDays(-1);
    }

    public function nextDay(): self
    {
        return $this->addDay();
    }

    public function previousDay(): self
    {
        return $this->subDay();
    }

    /** Tomorrow relative to this date. */
    public function tomorrow(): self
    {
        return $this->addDay();
    }

    /** Yesterday relative to this date. */
    public function yesterday(): self
    {
        return $this->subDay();
    }

    public function isTomorrow(): bool
    {
        return $this->equals(self::now()->tomorrow());
    }

    public function isYesterday(): bool
    {
        return $this->equals(self::now()->yesterday());
    }

    public function nextMonth(): self
    {
        return $this->addMonths(1);
    }

    public function previousMonth(): self
    {
        return $this->addMonths(-1);
    }

    public function nextYear(): self
    {
        return $this->addYears(1);
    }

    public function previousYear(): self
    {
        return $this->addYears(-1);
    }

    /* ------------------------------------------------------------------
     | Periods
     | ------------------------------------------------------------------ */

    public function startOfMonth(): self
    {
        return new self($this->year, $this->month, 1, $this->ad);
    }

    public function endOfMonth(): self
    {
        return new self($this->year, $this->month, $this->daysInMonth(), $this->ad);
    }

    public function startOfYear(): self
    {
        return new self($this->year, 1, 1, $this->ad);
    }

    public function endOfYear(): self
    {
        return new self($this->year, 12, Calendar::daysInBsMonth($this->year, 12), $this->ad);
    }

    /** Shrawan 1 of the fiscal year containing this date. */
    public function startOfFiscalYear(): self
    {
        return $this->fiscalYear()->startDate();
    }

    /** Ashadh 31 of the fiscal year containing this date. */
    public function endOfFiscalYear(): self
    {
        return $this->fiscalYear()->endDate();
    }

    public function startOfQuarter(): self
    {
        $month = ($this->quarter() - 1) * 3 + 1;

        return new self($this->year, $month, 1, $this->ad);
    }

    public function endOfQuarter(): self
    {
        $month = $this->quarter() * 3;

        return new self($this->year, $month, Calendar::daysInBsMonth($this->year, $month), $this->ad);
    }

    public function startOfWeek(?string $weekStartsOn = null): self
    {
        $target = strtolower($weekStartsOn ?? (string) Config::get('nepali-calendar.week_starts_on', 'sunday')) === 'monday' ? 2 : 1;

        return $this->subDays(($this->weekDay() - $target + 7) % 7);
    }

    public function endOfWeek(?string $weekStartsOn = null): self
    {
        return $this->startOfWeek($weekStartsOn)->addDays(6);
    }

    public function firstOfMonth(): self
    {
        return $this->startOfMonth();
    }

    public function lastOfMonth(): self
    {
        return $this->endOfMonth();
    }

    /**
     * The inclusive range from this date to another value
     * (e.g. $date->rangeTo('2081-12-31')).
     */
    public function rangeTo(mixed $end): NepaliDateRange
    {
        return NepaliDateRange::between($this, $end);
    }

    /* ------------------------------------------------------------------
     | Diffs
     | ------------------------------------------------------------------ */

    public function diffInDays(?self $other = null, bool $absolute = true): int
    {
        $other ??= self::now();

        $diff = Calendar::toEpochDay($other->year, $other->month, $other->day)
            - Calendar::toEpochDay($this->year, $this->month, $this->day);

        return $absolute ? abs($diff) : $diff;
    }

    public function diffInWeeks(?self $other = null, bool $absolute = true): int
    {
        $diff = $this->diffInDays($other, $absolute);

        return (int) floor($diff / 7);
    }

    /** Seconds between the two AD instants (defaults to now). */
    public function diffInSeconds(?self $other = null, bool $absolute = true): int
    {
        $other ??= self::now();

        $diff = $other->ad->getTimestamp() - $this->ad->getTimestamp();

        return $absolute ? abs($diff) : $diff;
    }

    public function diffInMinutes(?self $other = null, bool $absolute = true): int
    {
        $diff = $this->diffInSeconds($other, false);

        return $absolute ? abs(intdiv($diff, 60)) : intdiv($diff, 60);
    }

    public function diffInHours(?self $other = null, bool $absolute = true): int
    {
        $diff = $this->diffInSeconds($other, false);

        return $absolute ? abs(intdiv($diff, 3600)) : intdiv($diff, 3600);
    }

    public function diffInMonths(?self $other = null, bool $absolute = true): int
    {
        $other ??= self::now();

        $diff = ($other->year - $this->year) * 12 + ($other->month - $this->month);

        if ($other->day < $this->day) {
            $diff--;
        }

        return $absolute ? abs($diff) : $diff;
    }

    public function diffInYears(?self $other = null, bool $absolute = true): int
    {
        $other ??= self::now();

        $diff = $other->year - $this->year;

        if ($other->month < $this->month || ($other->month === $this->month && $other->day < $this->day)) {
            $diff--;
        }

        return $absolute ? abs($diff) : $diff;
    }

    /**
     * Broken-down difference as [years, months, days].
     *
     * 2080-11-05 -> 2083-04-12 is [2, 5, 7]: two full years, five further
     * months and seven further days. Uses BS-native arithmetic with the same
     * month-end clamping as addMonths()/addYears().
     */
    public function diffInYearsMonthsDays(?self $other = null, bool $absolute = true): array
    {
        $other ??= self::now();

        $forward = $this->compareTo($other) <= 0;
        $sign = $absolute || $forward ? 1 : -1;
        [$from, $to] = $forward ? [$this, $other] : [$other, $this];

        $years = $from->diffInYears($to);
        $months = $from->addYears($years)->diffInMonths($to);
        $days = $from->addYears($years)->addMonths($months)->diffInDays($to);

        return [$sign * $years, $sign * $months, $sign * $days];
    }

    /** Age as [years, months, days] completed by $at (default: now). */
    public function ageInYearsMonthsDays(?self $at = null): array
    {
        $at ??= self::now();

        return $this->isAfter($at) ? [0, 0, 0] : $this->diffInYearsMonthsDays($at, absolute: false);
    }

    /** Full years from this date (a birth date) until now. */
    public function age(): int
    {
        return $this->diffInYears(self::now(), absolute: false);
    }

    /** Full years completed by $other (a future instant); 0 if not born yet. */
    public function ageAt(mixed $other): int
    {
        $other = self::parse($other);

        return $this->isAfter($other) ? 0 : $this->diffInYears($other, absolute: false);
    }

    /** True when $today (default: now) is this date's birthday. */
    public function isBirthday(?self $today = null): bool
    {
        $today ??= self::now();

        if ($this->month !== $today->month) {
            return false;
        }

        if ($this->day === $today->day) {
            return true;
        }

        // Born on a month-end day that does not exist this year (e.g. a
        // 32-day Ashadh birthday in a 31-day Ashadh year): celebrate on the
        // last day of the month.
        $lastDay = Calendar::daysInBsMonth($today->year, $today->month);

        return $this->day > $lastDay && $today->day === $lastDay;
    }

    /** The next occurrence of this date's birthday (strictly after $from). */
    public function nextBirthday(?self $from = null): self
    {
        $from ??= self::now();

        $candidate = function (int $year): self {
            $day = min($this->day, Calendar::daysInBsMonth($year, $this->month));

            return new self($year, $this->month, $day);
        };

        $next = $candidate($from->year);

        if ($next->isBefore($from) || $next->equals($from)) {
            $next = $candidate($from->year + 1);
        }

        return $next;
    }

    /**
     * Human friendly description of the gap between this date and $other
     * (default: now). Uses Nepali by default, English on request.
     */
    public function diffForHumans(?self $other = null, ?string $language = null): string
    {
        $other ??= self::now();
        $language = $language ?? (string) Config::get('nepali-calendar.language', 'nepali');

        $days = $this->diffInDays($other, absolute: false);
        $nepali = $language === 'nepali';

        if ($days === 0) {
            return $nepali ? 'अहिले' : 'just now';
        }

        $past = $days > 0; // this date is behind $other

        if (abs($days) === 1) {
            return match (true) {
                $nepali && $past => 'हिजो',
                $nepali => 'भोलि',
                $past => 'yesterday',
                default => 'tomorrow',
            };
        }

        if (abs($days) < 30) {
            return $nepali
                ? NumberConverter::toNepali(abs($days)).' दिन '.($past ? 'अघि' : 'पछि')
                : abs($days).' days '.($past ? 'ago' : 'from now');
        }

        $months = $this->diffInMonths($other, absolute: true);

        if ($months < 12) {
            return $nepali
                ? NumberConverter::toNepali($months).' महिना '.($past ? 'अघि' : 'पछि')
                : $months.' '.($months === 1 ? 'month' : 'months').' '.($past ? 'ago' : 'from now');
        }

        $years = $this->diffInYears($other, absolute: true);

        return $nepali
            ? NumberConverter::toNepali($years).' वर्ष '.($past ? 'अघि' : 'पछि')
            : $years.' '.($years === 1 ? 'year' : 'years').' '.($past ? 'ago' : 'from now');
    }

    /* ------------------------------------------------------------------
     | Comparison
     | ------------------------------------------------------------------ */

    public function compareTo(self $other): int
    {
        return Calendar::toEpochDay($this->year, $this->month, $this->day)
            <=> Calendar::toEpochDay($other->year, $other->month, $other->day);
    }

    public function equals(mixed $other): bool
    {
        if (! $other instanceof self) {
            try {
                $other = self::parse($other);
            } catch (InvalidNepaliDateException) {
                return false;
            }
        }

        return $this->isSameDay($other);
    }

    public function isSameDay(self $other): bool
    {
        return $this->year === $other->year
            && $this->month === $other->month
            && $this->day === $other->day;
    }

    public function isSameMonth(self $other): bool
    {
        return $this->year === $other->year && $this->month === $other->month;
    }

    public function isSameYear(self $other): bool
    {
        return $this->year === $other->year;
    }

    /** Whether both dates fall in the same week of the BS year. */
    public function isSameWeek(self $other): bool
    {
        return $this->isSameYear($other) && $this->weekOfYear() === $other->weekOfYear();
    }

    /** Whether both dates fall in the same calendar quarter (Baisakh-Jeth = Q1). */
    public function isSameQuarter(self $other): bool
    {
        return $this->isSameYear($other) && $this->quarter() === $other->quarter();
    }

    /** Whether both dates fall in the same Nepali fiscal year (Shrawan based). */
    public function isSameFiscalYear(self $other): bool
    {
        return $this->fiscalYear()->year() === $other->fiscalYear()->year();
    }

    public function isBefore(mixed $other): bool
    {
        return $this->compareTo(self::coerce($other)) < 0;
    }

    public function isAfter(mixed $other): bool
    {
        return $this->compareTo(self::coerce($other)) > 0;
    }

    public function isBetween(self $start, self $end, bool $inclusive = true): bool
    {
        if ($start->isAfter($end)) {
            [$start, $end] = [$end, $start];
        }

        $startCmp = $this->compareTo($start);
        $endCmp = $this->compareTo($end);

        if ($inclusive) {
            return $startCmp >= 0 && $endCmp <= 0;
        }

        return $startCmp > 0 && $endCmp < 0;
    }

    public function isToday(): bool
    {
        return $this->isSameDay(self::now());
    }

    public function isPast(): bool
    {
        return $this->isBefore(self::now());
    }

    public function isFuture(): bool
    {
        return $this->isAfter(self::now());
    }

    public function isSaturday(): bool
    {
        return $this->weekDay() === 7;
    }

    public function isSunday(): bool
    {
        return $this->weekDay() === 1;
    }

    /**
     * Whether the day is a configured weekend day (default: Saturday).
     */
    public function isWeekend(): bool
    {
        $weekend = Config::get('nepali-calendar.weekend', [6]);
        $weekend = is_array($weekend) && $weekend !== [] ? array_map('intval', $weekend) : [6];

        return in_array((int) $this->ad->format('w'), $weekend, true);
    }

    /** Whether the day is a configured holiday. */
    public function isHoliday(): bool
    {
        return HolidayRepository::instance()->contains($this);
    }

    /** The holiday falling on this day, if any. */
    public function holiday(): ?NepaliHoliday
    {
        return HolidayRepository::instance()->forDate($this);
    }

    /** A working day: not a weekend day and not a holiday. */
    public function isBusinessDay(): bool
    {
        return ! $this->isWeekend() && ! $this->isHoliday();
    }

    public function isWorkingDay(): bool
    {
        return $this->isBusinessDay();
    }

    /** A weekday: not a configured weekend day (holidays do not count). */
    public function isWeekday(): bool
    {
        return ! $this->isWeekend();
    }

    /** The next day that is not a weekend day. */
    public function nextWeekday(): self
    {
        $date = $this;
        do {
            $date = $date->addDay();
        } while ($date->isWeekend());

        return $date;
    }

    /** The previous day that is not a weekend day. */
    public function previousWeekday(): self
    {
        $date = $this;
        do {
            $date = $date->subDay();
        } while ($date->isWeekend());

        return $date;
    }

    public function nextBusinessDay(): self
    {
        return $this->addBusinessDays(1);
    }

    public function previousBusinessDay(): self
    {
        return $this->addBusinessDays(-1);
    }

    /**
     * Move forward (positive) or backward (negative) by business days,
     * skipping weekends and configured holidays.
     */
    public function addBusinessDays(int $days): self
    {
        if ($days === 0) {
            return $this;
        }

        if (abs($days) > 366_000) {
            throw new InvalidNepaliDateException(
                "Cannot add [{$days}] business days: the step exceeds the supported calendar range."
            );
        }

        $step = $days > 0 ? 1 : -1;
        $remaining = abs($days);
        $date = $this;

        while ($remaining > 0) {
            $date = $date->addDays($step);

            if ($date->isBusinessDay()) {
                $remaining--;
            }
        }

        return $date;
    }

    public function subBusinessDays(int $days): self
    {
        return $this->addBusinessDays(-$days);
    }

    /**
     * Business days between this date (exclusive) and $other (inclusive),
     * signed: positive when $other is ahead, negative when behind.
     */
    public function businessDaysUntil(mixed $other): int
    {
        $other = self::coerce($other);

        if ($this->equals($other)) {
            return 0;
        }

        $direction = $this->isBefore($other) ? 1 : -1;
        $count = 0;
        $date = $this;

        while (! $date->equals($other)) {
            $date = $date->addDays($direction);

            if ($date->isBusinessDay()) {
                $count++;
            }
        }

        return $direction * $count;
    }

    /* ------------------------------------------------------------------
     | Calendar grid
     | ------------------------------------------------------------------ */

    /**
     * Build a month grid for UI calendars: an array of weeks, each week an
     * array of 7 slots (NepaliDate or null for days outside the month).
     *
     * @return array<int, array<int, self|null>>
     */
    public function calendar(?string $weekStartsOn = null): array
    {
        $first = new self($this->year, $this->month, 1);
        $last = $this->endOfMonth();

        $cursor = $first->startOfWeek($weekStartsOn);
        $weeks = [];

        while (true) {
            $week = [];
            for ($i = 0; $i < 7; $i++) {
                $week[] = ($cursor->year === $this->year && $cursor->month === $this->month) ? $cursor : null;
                $cursor = $cursor->addDays(1);
            }
            $weeks[] = $week;

            if ($cursor->isAfter($last)) {
                break;
            }
        }

        return $weeks;
    }

    /* ------------------------------------------------------------------
     | Immutable modifiers
     | ------------------------------------------------------------------ */

    public function withYear(int $year): self
    {
        return new self($year, $this->month, min($this->day, Calendar::daysInBsMonth($year, $this->month)), $this->ad);
    }

    public function withMonth(int $month): self
    {
        return new self($this->year, $month, min($this->day, Calendar::daysInBsMonth($this->year, $month)), $this->ad);
    }

    public function withDay(int $day): self
    {
        return new self($this->year, $this->month, $day, $this->ad);
    }

    public function copy(): self
    {
        return new self($this->year, $this->month, $this->day, $this->ad);
    }

    /* ------------------------------------------------------------------
     | Serialization / magic
     | ------------------------------------------------------------------ */

    public function toArray(): array
    {
        return [
            'year' => $this->year,
            'month' => $this->month,
            'day' => $this->day,
            'num_week_day' => $this->weekDay(),
            'iso_week_day' => $this->weekDayIso(),
            'week_day' => $this->weekDayName(),
            'week_day_short' => $this->weekDayShort(),
            'month_name' => $this->monthName(),
            'month_short' => $this->monthShortName(),
            'day_of_year' => $this->dayOfYear(),
            'days_in_month' => $this->daysInMonth(),
            'days_in_year' => $this->daysInYear(),
            'is_leap_year' => $this->isLeapYear(),
            'ad_date' => $this->ad->format('Y-m-d'),
            'ad_day_name' => $this->ad->format('l'),
            'ad_month_name' => $this->ad->format('F'),
            'english' => $this->toEnglishNumerals(),
            'nepali' => $this->toNepaliNumerals(),
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function toJson($options = 0): string
    {
        return json_encode($this->jsonSerialize(), $options);
    }

    public function __serialize(): array
    {
        return ['year' => $this->year, 'month' => $this->month, 'day' => $this->day];
    }

    public function __unserialize(array $data): void
    {
        $this->__construct(
            (int) ($data['year'] ?? 2000),
            (int) ($data['month'] ?? 1),
            (int) ($data['day'] ?? 1)
        );
    }

    public function __get(string $name): mixed
    {
        return match ($name) {
            'year' => $this->year,
            'month' => $this->month,
            'day' => $this->day,
            'quarter' => $this->quarter(),
            'weekDay', 'week_day' => $this->weekDay(),
            'weekDayIso', 'iso_week_day' => $this->weekDayIso(),
            'weekDayName', 'week_day_name' => $this->weekDayName(),
            'monthName', 'month_name' => $this->monthName(),
            'dayOfYear', 'day_of_year' => $this->dayOfYear(),
            'daysInMonth', 'days_in_month' => $this->daysInMonth(),
            'daysInYear', 'days_in_year' => $this->daysInYear(),
            'isLeapYear', 'is_leap_year' => $this->isLeapYear(),
            'ad' => $this->ad(),
            'timestamp' => $this->timestamp(),
            default => throw new InvalidNepaliDateException("Undefined property [{$name}] on NepaliDate."),
        };
    }

    public function __isset(string $name): bool
    {
        try {
            $this->__get($name);

            return true;
        } catch (InvalidNepaliDateException) {
            return false;
        }
    }

    private static function coerce(mixed $other): self
    {
        if ($other instanceof self) {
            return $other;
        }

        return self::parse($other);
    }
}
