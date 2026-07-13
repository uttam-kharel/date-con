<?php

declare(strict_types=1);

namespace Sambat\NepaliCalendar\Rules;

use Illuminate\Contracts\Validation\ValidationRule;
use Sambat\NepaliCalendar\NepaliDate;

/**
 * Validates that a value is a valid Nepali date strictly before another date.
 *
 * Usage: 'end_date' => ['required', new NepaliDateBeforeRule('2083-04-01')]
 */
final class NepaliDateBeforeRule implements ValidationRule
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

        if (! NepaliDate::parse($value)->isBefore($this->limit)) {
            $fail("The :attribute must be a date before {$this->limit->toDateString()}.");
        }
    }
}
