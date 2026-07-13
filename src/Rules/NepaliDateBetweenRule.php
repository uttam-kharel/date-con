<?php

declare(strict_types=1);

namespace Sambat\NepaliCalendar\Rules;

use Illuminate\Contracts\Validation\ValidationRule;
use Sambat\NepaliCalendar\NepaliDate;

/**
 * Validates that a value is a valid Nepali date within (inclusive) two dates.
 *
 * Usage: 'event_date' => ['required', new NepaliDateBetweenRule('2083-01-01', '2083-12-31')]
 */
final class NepaliDateBetweenRule implements ValidationRule
{
    private readonly NepaliDate $start;

    private readonly NepaliDate $end;

    public function __construct(mixed $start, mixed $end)
    {
        $this->start = NepaliDate::parse($start);
        $this->end = NepaliDate::parse($end);
    }

    public function validate(string $attribute, mixed $value, \Closure $fail): void
    {
        if (! NepaliDate::isValid($value)) {
            $fail('The :attribute must be a valid Nepali (Bikram Sambat) date.');

            return;
        }

        if (! NepaliDate::parse($value)->isBetween($this->start, $this->end)) {
            $fail("The :attribute must be a date between {$this->start->toDateString()} and {$this->end->toDateString()}.");
        }
    }
}
