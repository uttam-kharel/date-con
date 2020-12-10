<?php

declare(strict_types=1);

namespace Sambat\NepaliCalendar\Exceptions;

use InvalidArgumentException;

/**
 * Thrown when a value cannot be interpreted as a valid Nepali date
 * (bad format, impossible day, unknown month name, ...).
 */
class InvalidNepaliDateException extends InvalidArgumentException
{
    public static function forValue(mixed $value): self
    {
        $shown = is_scalar($value)
            ? (string) $value
            : get_debug_type($value);

        return new self(
            "Unable to parse [{$shown}] as a Nepali (Bikram Sambat) date. "
            .'Use YYYY-MM-DD (e.g. "2081-11-28"), a month name (e.g. "2081 Falgun 28") '
            .'or provide year, month and day explicitly.'
        );
    }
}
