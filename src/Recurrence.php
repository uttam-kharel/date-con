<?php

declare(strict_types=1);

namespace Sambat\NepaliCalendar;

use ArrayIterator;
use Countable;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use IteratorAggregate;
use JsonSerializable;
use Sambat\NepaliCalendar\Constants\CalendarData;
use Sambat\NepaliCalendar\Exceptions\InvalidNepaliDateException;
use Traversable;

/**
 * A recurring BS date rule.
 *
 *     $dates = Recurrence::monthly('2083-01-05')
 *         ->every(2)
 *         ->until('2083-12-31')
 *         ->dates();
 *
 * Frequencies: daily, weekly, monthly, yearly. Weekly rules can be
 * restricted to specific weekdays with on() ('monday' | 1 = Sunday ..
 * 7 = Saturday). Materialization is deliberately guarded: a rule can
 * never produce more than MAX_OCCURRENCES dates, so an unbounded rule
 * fails loudly instead of looping forever.
 */
final class Recurrence implements Arrayable, Countable, IteratorAggregate, Jsonable, JsonSerializable
{
    /** Hard ceiling for any single rule. */
    public const MAX_OCCURRENCES = 10000;

    private string $frequency = 'daily';

    private int $every = 1;

    private ?NepaliDate $start = null;

    private ?NepaliDate $end = null;

    private ?int $limit = null;

    /** @var list<int> weekday numbers 1 (Sunday) .. 7 (Saturday) */
    private array $weekdays = [];

    /** @var list<NepaliDate>|null */
    private ?array $cache = null;

    public static function daily(mixed $start = null): self
    {
        return (new self)->setFrequency('daily')->from($start);
    }

    public static function weekly(mixed $start = null): self
    {
        return (new self)->setFrequency('weekly')->from($start);
    }

    public static function monthly(mixed $start = null): self
    {
        return (new self)->setFrequency('monthly')->from($start);
    }

    public static function yearly(mixed $start = null): self
    {
        return (new self)->setFrequency('yearly')->from($start);
    }

    private function setFrequency(string $frequency): self
    {
        $this->frequency = $frequency;

        return $this;
    }

    public function from(mixed $start): self
    {
        $this->start = $start === null ? null : NepaliDate::parse($start);

        return $this;
    }

    /** Repeat every N units (e.g. every(2)->monthly = every second month). */
    public function every(int $interval): self
    {
        if ($interval < 1) {
            throw new InvalidNepaliDateException("Invalid interval [{$interval}]. Interval must be at least 1.");
        }

        $this->every = $interval;

        return $this;
    }

    /** Restrict a weekly rule to specific weekdays: 'monday', 1 (Sunday) .. 7 (Saturday). */
    public function on(mixed ...$weekdays): self
    {
        if ($this->frequency !== 'weekly') {
            throw new InvalidNepaliDateException('on() is only supported for weekly recurrence.');
        }

        $this->weekdays = array_map(fn (int|string $day) => self::resolveWeekday($day), $weekdays);
        sort($this->weekdays);

        return $this;
    }

    public function until(mixed $end): self
    {
        $this->end = NepaliDate::parse($end);

        return $this;
    }

    public function between(mixed $start, mixed $end): self
    {
        return $this->from($start)->until($end);
    }

    public function take(int $limit): self
    {
        if ($limit < 1) {
            throw new InvalidNepaliDateException("Invalid limit [{$limit}]. Limit must be at least 1.");
        }

        $this->limit = $limit;

        return $this;
    }

    public function frequency(): string
    {
        return $this->frequency;
    }

    public function interval(): int
    {
        return $this->every;
    }

    public function start(): ?NepaliDate
    {
        return $this->start;
    }

    public function end(): ?NepaliDate
    {
        return $this->end;
    }

    /** @return list<NepaliDate> every occurrence, ascending */
    public function dates(): array
    {
        return $this->cache ??= $this->materialize();
    }

    public function count(): int
    {
        return count($this->dates());
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->dates());
    }

    /** @return list<string> every occurrence as "Y-m-d" strings */
    public function toDateStrings(string $separator = '-'): array
    {
        return array_map(fn (NepaliDate $date) => $date->toDateString($separator), $this->dates());
    }

    public function toArray(): array
    {
        return [
            'frequency' => $this->frequency,
            'every' => $this->every,
            'weekdays' => $this->weekdays,
            'start' => $this->start?->toDateString(),
            'end' => $this->end?->toDateString(),
            'limit' => $this->limit,
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

    /** @return list<NepaliDate> */
    private function materialize(): array
    {
        $start = $this->start ?? NepaliDate::now();

        return match ($this->frequency) {
            'daily' => $this->materializeDaily($start),
            'weekly' => $this->materializeWeekly($start),
            'monthly' => $this->materializeMonths($start, addMonths: true),
            'yearly' => $this->materializeMonths($start, addMonths: false),
            default => throw new InvalidNepaliDateException("Unknown frequency [{$this->frequency}]."),
        };
    }

    /** @return list<NepaliDate> */
    private function materializeDaily(NepaliDate $start): array
    {
        $dates = [];
        $cursor = $start;

        while ($this->within($cursor) && $this->push($dates, $cursor)) {
            $cursor = $cursor->addDays($this->every);
        }

        return $dates;
    }

    /** @return list<NepaliDate> */
    private function materializeWeekly(NepaliDate $start): array
    {
        $dates = [];

        if ($this->weekdays === []) {
            $cursor = $start;

            while ($this->within($cursor) && $this->push($dates, $cursor)) {
                $cursor = $cursor->addWeeks($this->every);
            }

            return $dates;
        }

        $windowStart = $start;

        while (true) {
            $weekStart = $windowStart->subDays($windowStart->weekDay() - 1);

            // Once the whole week is past the end date the rule is complete.
            if ($this->end !== null && $weekStart->isAfter($this->end)) {
                return $dates;
            }

            foreach ($this->weekdays as $weekday) {
                // The given weekday inside the current N-week window.
                $candidate = $weekStart->addDays($weekday - 1);

                if ($candidate->isBefore($start) || ! $this->within($candidate)) {
                    continue;
                }

                if (! $this->push($dates, $candidate)) {
                    return $dates;
                }
            }

            $windowStart = $windowStart->addWeeks($this->every);
        }
    }

    /**
     * @return list<NepaliDate>
     */
    private function materializeMonths(NepaliDate $start, bool $addMonths): array
    {
        $dates = [];
        $cursor = $start;

        while ($this->within($cursor) && $this->push($dates, $cursor)) {
            $cursor = $addMonths ? $cursor->addMonths($this->every) : $cursor->addYears($this->every);
        }

        return $dates;
    }

    private function within(NepaliDate $date): bool
    {
        return $this->end === null || ! $date->isAfter($this->end);
    }

    /**
     * Append an occurrence; returns false when the rule is complete.
     */
    private function push(array &$dates, NepaliDate $date): bool
    {
        $dates[] = $date;

        if ($this->limit !== null && count($dates) >= $this->limit) {
            return false;
        }

        if (count($dates) > self::MAX_OCCURRENCES) {
            throw new InvalidNepaliDateException(
                'Recurrence produced more than '.self::MAX_OCCURRENCES.' occurrences. '
                .'Bound the rule with until()/between() or take().'
            );
        }

        return true;
    }

    private static function resolveWeekday(int|string $weekday): int
    {
        if (is_int($weekday)) {
            if ($weekday < 1 || $weekday > 7) {
                throw new InvalidNepaliDateException(
                    "Invalid weekday [{$weekday}]. Use 1 (Sunday) .. 7 (Saturday) or a name."
                );
            }

            return $weekday;
        }

        $name = strtolower(trim($weekday));

        foreach ([CalendarData::WEEK_DAYS_ENGLISH, CalendarData::WEEK_DAYS_ROMAN] as $names) {
            foreach ($names as $number => $day) {
                if (strtolower($day) === $name) {
                    return $number;
                }
            }
        }

        throw new InvalidNepaliDateException("Unknown weekday [{$weekday}].");
    }
}
