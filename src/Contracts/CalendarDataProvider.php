<?php

declare(strict_types=1);

namespace Sambat\NepaliCalendar\Contracts;

/**
 * Supplies the raw Bikram Sambat month-length table used by the conversion
 * engine. Swap implementations (built-in constants, database, API, ...)
 * without touching any conversion code.
 */
interface CalendarDataProvider
{
    /**
     * Days in each month (Baisakh .. Chaitra) for every supported BS year.
     *
     * @return array<int, array<int, int>>
     */
    public function allMonthLengths(): array;

    /**
     * First BS year available in the data.
     */
    public function minYear(): int;

    /**
     * Last BS year available in the data.
     */
    public function maxYear(): int;
}
