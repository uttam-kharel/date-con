<?php

declare(strict_types=1);

namespace Sambat\NepaliCalendar\Rules;

use Illuminate\Contracts\Validation\ValidationRule;
use Sambat\NepaliCalendar\NepaliDate;

/**
 * Validates that a value is a valid Nepali date strictly after another date.
 *
 * Usage: 'start_date' => ['required', new NepaliDateAfterRule('2083-01-01')]
 */
final class NepaliDateAfterRule implements ValidationRule
{
    private readonly NepaliDate $limit;

    public function __construct(mixed $limit)
    {
        $this->limit = NepaliDate::parse($limit);
    }

    public function validate(string $attribute, mixed $value, \Closure $fail): void
    {
        if (! NepaliDate::isValid($value)) {
            $fail('The :attribute must be a valid Nepali (Bikram Sambat) date.');

            return;
        }

        if (! NepaliDate::parse($value)->isAfter($this->limit)) {
            $fail("The :attribute must be a date after {$this->limit->toDateString()}.");
        }
    }
}
