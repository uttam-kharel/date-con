<?php

declare(strict_types=1);

namespace Sambat\NepaliCalendar\Rules;

use Illuminate\Contracts\Validation\ValidationRule;
use Sambat\NepaliCalendar\NepaliDate;

/**
 * Validates that a value is a valid Nepali (Bikram Sambat) date.
 *
 * Usage: 'date' => ['required', new NepaliDateRule]
 */
final class NepaliDateRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, \Closure $fail): void
    {
        if (! NepaliDate::isValid($value)) {
            $fail('The :attribute must be a valid Nepali (Bikram Sambat) date.');
        }
    }
}
