<?php

declare(strict_types=1);

namespace Sambat\NepaliCalendar;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use JsonSerializable;
use Sambat\NepaliCalendar\Exceptions\InvalidNepaliDateException;
use Stringable;

/**
 * A Nepali fiscal year: Shrawan 1 of BS year N through Ashadh (month 12) of
 * BS year N+1. Fiscal year 2083 therefore runs 2083-04-01 .. 2084-12-31 and
 * its label is "2083/84" — the convention used by the Government of Nepal,
 * banks, hospitals and ERP systems.
 *
 * Note: the end date may fall in year N+1, so fiscal years whose end year is
 * beyond the supported calendar range (e.g. 2099) throw the usual typed
 * out-of-range exception rather than returning wrong data.
 */
final class NepaliFiscalYear implements Arrayable, Jsonable, JsonSerializable, Stringable
{
    /** Shrawan — the month that starts the fiscal year. */
    public const START_MONTH = 4;

    private readonly int $year;

    public function __construct(int $year)
    {
        $this->year = $year;
    }

    /** The fiscal year that starts in the given BS year (Shrawan 1). */
    public static function forYear(int $year): self
    {
        return new self($year);
    }

    /** The fiscal year containing the given date. */
    public static function fromDate(mixed $date): self
    {
        $date = NepaliDate::parse($date);

        return new self($date->month() >= self::START_MONTH ? $date->year() : $date->year() - 1);
    }

    /** The BS year that the fiscal year starts in (e.g. 2083 for "2083/84"). */
    public function year(): int
    {
        return $this->year;
    }

    /** The conventional label, e.g. "2083/84" (short second year). */
    public function label(): string
    {
        return sprintf('%d/%02d', $this->year, ($this->year + 1) % 100);
    }

    /** Shrawan 1 of the start year. */
    public function startDate(): NepaliDate
    {
        return new NepaliDate($this->year, self::START_MONTH, 1);
    }

    /** Ashadh 31 (the last day of month 3) of the following year. */
    public function endDate(): NepaliDate
    {
        return (new NepaliDate($this->year + 1, 3, 1))->endOfMonth();
    }

    /** Total days in the fiscal year. */
    public function days(): int
    {
        return $this->startDate()->diffInDays($this->endDate()) + 1;
    }

    public function contains(mixed $date): bool
    {
        $date = NepaliDate::parse($date);

        return ! $date->isBefore($this->startDate()) && ! $date->isAfter($this->endDate());
    }

    /**
     * Fiscal quarter (1-4) of the given date (defaults to now).
     *
     * Q1 = Shrawan–Ashwin, Q2 = Kartik–Poush, Q3 = Magh–Chaitra,
     * Q4 = Baisakh–Ashadh.
     */
    public function quarter(?NepaliDate $date = null): int
    {
        $date ??= NepaliDate::now();

        if (! $this->contains($date)) {
            throw new InvalidNepaliDateException(
                "Date [{$date->toDateString()}] is not within fiscal year {$this->label()}."
            );
        }

        return $date->fiscalQuarter();
    }

    /**
     * The four fiscal quarters as ranges.
     *
     * @return list<array{number: int, label: string, start: NepaliDate, end: NepaliDate}>
     */
    public function quarters(): array
    {
        return array_map(
            fn (int $quarter) => [
                'number' => $quarter,
                'label' => sprintf('%s Q%d', $this->label(), $quarter),
                'start' => $this->quarterRange($quarter)->start(),
                'end' => $this->quarterRange($quarter)->end(),
            ],
            range(1, 4)
        );
    }

    /** The inclusive date range of a fiscal quarter (1-4). */
    public function quarterRange(int $quarter): NepaliDateRange
    {
        if ($quarter < 1 || $quarter > 4) {
            throw new InvalidNepaliDateException(
                "Invalid fiscal quarter [{$quarter}]. Must be between 1 and 4."
            );
        }

        $startMonth = self::START_MONTH + ($quarter - 1) * 3; // 4, 7, 10, 13
        $startYear = $this->year + intdiv($startMonth - 1, 12);
        $startMonth = (($startMonth - 1) % 12) + 1;

        $endMonth = $startMonth + 2;
        $endYear = $startYear + intdiv($endMonth - 1, 12);
        $endMonth = (($endMonth - 1) % 12) + 1;

        return NepaliDateRange::fromDates(
            new NepaliDate($startYear, $startMonth, 1),
            (new NepaliDate($endYear, $endMonth, 1))->endOfMonth()
        );
    }

    public function isCurrent(): bool
    {
        return $this->contains(NepaliDate::now());
    }

    public function toArray(): array
    {
        return [
            'label' => $this->label(),
            'year' => $this->year,
            'start' => $this->startDate()->toDateString(),
            'end' => $this->endDate()->toDateString(),
            'days' => $this->days(),
            'is_current' => $this->isCurrent(),
            'quarters' => array_map(
                fn (array $quarter) => [
                    'number' => $quarter['number'],
                    'label' => $quarter['label'],
                    'start' => $quarter['start']->toDateString(),
                    'end' => $quarter['end']->toDateString(),
                ],
                $this->quarters()
            ),
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function toJson($options = 0): string
    {
        return json_encode($this->toArray(), $options);
    }

    public function __toString(): string
    {
        return $this->label();
    }
}
