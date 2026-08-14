<?php

declare(strict_types=1);

namespace Sambat\NepaliCalendar;

use ArrayIterator;
use Countable;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use IteratorAggregate;
use JsonSerializable;
use Sambat\NepaliCalendar\Exceptions\InvalidNepaliDateException;
use Stringable;
use Traversable;

/**
 * An immutable, inclusive range of BS dates.
 *
 * Ranges are created with NepaliDateRange::between(...). Bounds are normalized,
 * so reversed bounds are swapped instead of producing an empty range. A range
 * iterates day by day and can be sliced into calendar weeks, BS months and BS
 * years — handy for reports, attendance, billing periods and fiscal quarters.
 */
final class NepaliDateRange implements Arrayable, Countable, IteratorAggregate, Jsonable, JsonSerializable, Stringable
{
    private readonly NepaliDate $start;

    private readonly NepaliDate $end;

    private function __construct(NepaliDate $start, NepaliDate $end)
    {
        if ($start->isAfter($end)) {
            [$start, $end] = [$end, $start];
        }

        $this->start = $start;
        $this->end = $end;
    }

    /** Build a range from any parseable values (strings, NepaliDate, Carbon, ...). */
    public static function between(mixed $start, mixed $end): self
    {
        return new self(NepaliDate::parse($start), NepaliDate::parse($end));
    }

    /** Build a range from two NepaliDate instances. */
    public static function fromDates(NepaliDate $start, NepaliDate $end): self
    {
        return new self($start, $end);
    }

    public function start(): NepaliDate
    {
        return $this->start;
    }

    public function end(): NepaliDate
    {
        return $this->end;
    }

    /** Total days in the range (inclusive). */
    public function count(): int
    {
        return $this->start->diffInDays($this->end) + 1;
    }

    public function isEmpty(): bool
    {
        return false; // normalized ranges always hold at least one day
    }

    public function contains(mixed $date): bool
    {
        $date = NepaliDate::parse($date);

        return ! $date->isBefore($this->start) && ! $date->isAfter($this->end);
    }

    /** Whether another range is fully inside this one. */
    public function containsRange(self $other): bool
    {
        return $this->contains($other->start) && $this->contains($other->end);
    }

    /** Whether two ranges share at least one day. */
    public function overlaps(self $other): bool
    {
        return ! $this->end->isBefore($other->start) && ! $other->end->isBefore($this->start);
    }

    /** Whether the ranges are adjacent (one ends the day before the other starts). */
    public function touches(self $other): bool
    {
        return $this->end->addDay()->equals($other->start)
            || $other->end->addDay()->equals($this->start);
    }

    /**
     * The merged range when the ranges overlap or touch, null otherwise.
     */
    public function merge(self $other): ?self
    {
        if (! $this->overlaps($other) && ! $this->touches($other)) {
            return null;
        }

        return self::fromDates(
            $this->start->isBefore($other->start) ? $this->start : $other->start,
            $this->end->isAfter($other->end) ? $this->end : $other->end
        );
    }

    /**
     * The days shared by both ranges, or null when they do not overlap.
     */
    public function intersection(self $other): ?self
    {
        if (! $this->overlaps($other)) {
            return null;
        }

        return self::fromDates(
            $this->start->isAfter($other->start) ? $this->start : $other->start,
            $this->end->isBefore($other->end) ? $this->end : $other->end
        );
    }

    /**
     * The days between two non-adjacent ranges, or null when they overlap
     * or touch.
     */
    public function gap(self $other): ?self
    {
        if ($this->overlaps($other) || $this->touches($other)) {
            return null;
        }

        $earlier = $this->end->isBefore($other->start) ? $this : $other;
        $later = $earlier === $this ? $other : $this;

        return self::fromDates($earlier->end->addDay(), $later->start->subDay());
    }

    /** Every N-th day within the range, starting at the range start. */
    public function daysEvery(int $step): array
    {
        if ($step < 1) {
            throw new InvalidNepaliDateException("Invalid step [{$step}]. Step must be at least 1.");
        }

        $days = [];
        for ($cursor = $this->start; ! $cursor->isAfter($this->end); $cursor = $cursor->addDays($step)) {
            $days[] = $cursor;
        }

        return $days;
    }

    /** @return list<NepaliDate> business days (not weekend, not holiday) in the range */
    public function businessDays(): array
    {
        return array_values(array_filter($this->days(), fn (NepaliDate $date) => $date->isBusinessDay()));
    }

    /** @return list<NepaliDate> alias of businessDays() */
    public function workingDays(): array
    {
        return $this->businessDays();
    }

    /** @return list<NepaliDate> weekend days in the range */
    public function weekends(): array
    {
        return array_values(array_filter($this->days(), fn (NepaliDate $date) => $date->isWeekend()));
    }

    /** @return list<NepaliDate> configured holidays in the range */
    public function holidays(): array
    {
        return array_values(array_filter($this->days(), fn (NepaliDate $date) => $date->isHoliday()));
    }

    /** Number of business days in the range. */
    public function businessDayCount(): int
    {
        return count($this->businessDays());
    }

    /** @return list<NepaliDate> every day in the range, ascending */
    public function days(): array
    {
        $days = [];
        for ($cursor = $this->start; ! $cursor->isAfter($this->end); $cursor = $cursor->addDay()) {
            $days[] = $cursor;
        }

        return $days;
    }

    /** @return list<self> the range sliced into calendar weeks (clamped to the range) */
    public function weeks(?string $weekStartsOn = null): array
    {
        return $this->group(fn (NepaliDate $date) => $date->startOfWeek($weekStartsOn)->toDateString());
    }

    /** @return list<self> the range sliced into BS calendar months (clamped to the range) */
    public function months(): array
    {
        return $this->group(fn (NepaliDate $date) => sprintf('%04d-%02d', $date->year(), $date->month()));
    }

    /** @return list<self> the range sliced into BS years (clamped to the range) */
    public function years(): array
    {
        return $this->group(fn (NepaliDate $date) => (string) $date->year());
    }

    /** @return list<string> every day as a "Y-m-d" string, ascending */
    public function toDateStrings(string $separator = '-'): array
    {
        return array_map(fn (NepaliDate $date) => $date->toDateString($separator), $this->days());
    }

    /**
     * Slice the range into contiguous sub-ranges by a grouping key.
     *
     * @return list<self>
     */
    private function group(callable $key): array
    {
        $groups = [];
        foreach ($this->days() as $date) {
            $groups[$key($date)][] = $date;
        }

        return array_map(
            fn (array $days) => self::fromDates($days[0], $days[array_key_last($days)]),
            array_values($groups)
        );
    }

    public function toArray(): array
    {
        return [
            'start' => $this->start->toDateString(),
            'end' => $this->end->toDateString(),
            'days' => $this->count(),
            'dates' => $this->toDateStrings(),
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
        return $this->start->toDateString().' to '.$this->end->toDateString();
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->days());
    }
}
