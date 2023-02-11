<?php

declare(strict_types=1);

namespace Sambat\NepaliCalendar\Rules;

use Illuminate\Contracts\Validation\ValidationRule;
use Sambat\NepaliCalendar\NepaliDate;

/**
 * Validates that a value is a valid Nepali date matching the given
 * format string (round-trip check).
 *
 * Usage: 'date' => ['required', new NepaliDateFormatRule('Y-m-d')]
 */
final class NepaliDateFormatRule implements ValidationRule
{
    public function __construct(private readonly string $format = 'Y-m-d') {}

    public function validate(string $attribute, mixed $value, \Closure $fail): void
    {
        try {
            $date = NepaliDate::parse($value);
        } catch (\Throwable) {
            $fail('The :attribute must be a valid Nepali (Bikram Sambat) date.');

            return;
        }

        if ($date->format($this->format, null, false) !== (string) $value) {
            $fail("The :attribute must be a valid Nepali date in the format {$this->format}.");
        }
    }
}
