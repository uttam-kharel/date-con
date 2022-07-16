<?php

declare(strict_types=1);

namespace Sambat\NepaliCalendar\Support;

use Sambat\NepaliCalendar\NepaliDate;

/**
 * Backing methods for the @nepaliDate / @nepaliDateHuman Blade directives.
 */
final class Blade
{
    public static function render(mixed $date, ?string $format = null): string
    {
        return NepaliDate::parse($date)->format($format ?? Config::get('nepali-calendar.default_format', 'Y-m-d'));
    }

    public static function human(mixed $date, mixed $other = null): string
    {
        return NepaliDate::parse($date)->diffForHumans($other);
    }
}
