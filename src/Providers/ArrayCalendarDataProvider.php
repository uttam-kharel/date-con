<?php

declare(strict_types=1);

namespace Sambat\NepaliCalendar\Providers;

use Sambat\NepaliCalendar\Constants\CalendarData;
use Sambat\NepaliCalendar\Contracts\CalendarDataProvider;

/**
 * The default driver. Serves the calendar data from the built-in,
 * observation-verified constant table - no database required, works
 * in plain PHP.
 */
final class ArrayCalendarDataProvider implements CalendarDataProvider
{
    public function allMonthLengths(): array
    {
        return CalendarData::NEPALI_YEARS;
    }

    public function minYear(): int
    {
        return CalendarData::BS_MIN_YEAR;
    }

    public function maxYear(): int
    {
        return CalendarData::BS_MAX_YEAR;
    }
}
