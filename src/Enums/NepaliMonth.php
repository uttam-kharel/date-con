<?php

declare(strict_types=1);

namespace Sambat\NepaliCalendar\Enums;

use Sambat\NepaliCalendar\Constants\CalendarData;

/**
 * The twelve months of the Bikram Sambat calendar.
 */
enum NepaliMonth: int
{
    case Baisakh = 1;
    case Jestha = 2;
    case Ashadh = 3;
    case Shrawan = 4;
    case Bhadra = 5;
    case Ashwin = 6;
    case Kartik = 7;
    case Mangsir = 8;
    case Poush = 9;
    case Magh = 10;
    case Falgun = 11;
    case Chaitra = 12;

    /** Devanagari name. */
    public function devanagari(): string
    {
        return CalendarData::MONTHS_DEVANAGARI[$this->value];
    }

    /** Latin transliteration (Baisakh, Jestha, ...). */
    public function roman(): string
    {
        return CalendarData::MONTHS_ROMAN[$this->value];
    }
}
