<?php

declare(strict_types=1);

namespace Sambat\NepaliCalendar\Exceptions;

/**
 * Thrown when a conversion or arithmetic result falls outside the
 * supported calendar range (BS 2000-01-01 .. BS 2099-12-30).
 */
class NepaliDateOutOfRangeException extends InvalidNepaliDateException
{
    public static function forBs(int $year, int $month, int $day): self
    {
        return new self(
            "The Nepali date {$year}-{$month}-{$day} is outside the supported range "
            .'(BS 2000-01-01 to BS 2099-12-30).'
        );
    }

    public static function forAd(int $year, int $month, int $day): self
    {
        return new self(
            "The Gregorian date {$year}-{$month}-{$day} is outside the supported range "
            .'(AD 1943-04-14 to AD 2043-04-13).'
        );
    }
}
