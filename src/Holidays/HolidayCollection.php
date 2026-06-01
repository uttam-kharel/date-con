<?php

declare(strict_types=1);

namespace Sambat\NepaliCalendar\Holidays;

use ArrayIterator;
use Countable;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use IteratorAggregate;
use JsonSerializable;
use Sambat\NepaliCalendar\NepaliDate;
use Traversable;

/**
 * An immutable collection of holidays with lookup helpers.
 *
 * @implements IteratorAggregate<int, NepaliHoliday>
 */
final class HolidayCollection implements Arrayable, Countable, IteratorAggregate, Jsonable, JsonSerializable
{
    /** @param list<NepaliHoliday> $holidays */
    public function __construct(private array $holidays = []) {}

    /** @return list<NepaliHoliday> */
    public function all(): array
    {
        return $this->holidays;
    }

    /** Holidays that fall within the given BS year. */
    public function forYear(int $year): self
    {
        return new self(array_values(array_filter(
            $this->holidays,
            fn (NepaliHoliday $holiday) => $holiday->date()->year() === $year
        )));
    }

    public function contains(NepaliDate $date): bool
    {
        return $this->forDate($date) !== null;
    }

    public function forDate(NepaliDate $date): ?NepaliHoliday
    {
        foreach ($this->holidays as $holiday) {
            if ($holiday->date()->equals($date)) {
                return $holiday;
            }
        }

        return null;
    }

    /** @return list<string> */
    public function names(): array
    {
        return array_map(fn (NepaliHoliday $holiday) => $holiday->name(), $this->holidays);
    }

    /** @return list<string> the holiday dates as "Y-m-d" strings */
    public function dates(): array
    {
        return array_map(fn (NepaliHoliday $holiday) => $holiday->date()->toDateString(), $this->holidays);
    }

    public function isEmpty(): bool
    {
        return $this->holidays === [];
    }

    public function count(): int
    {
        return count($this->holidays);
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->holidays);
    }

    public function toArray(): array
    {
        return array_map(fn (NepaliHoliday $holiday) => $holiday->toArray(), $this->holidays);
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function toJson($options = 0): string
    {
        return json_encode($this->toArray(), $options);
    }
}
