<?php

declare(strict_types=1);

namespace Sambat\NepaliCalendar\Facades;

use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Facade;
use Sambat\NepaliCalendar\NepaliDate as NepaliDateObject;
use Sambat\NepaliCalendar\NepaliDateFactory;

/**
 * @method static NepaliDateObject now()
 * @method static NepaliDateObject today()
 * @method static NepaliDateObject parse(mixed $value)
 * @method static NepaliDateObject fromAd(mixed $value)
 * @method static NepaliDateObject fromBs(int $year, int $month, int $day, ?CarbonInterface $time = null)
 * @method static NepaliDateObject fromCarbon(CarbonInterface $carbon)
 * @method static NepaliDateObject fromTimestamp(int $timestamp)
 * @method static bool isValid(mixed $value)
 * @method static int daysInMonth(int $year, int $month)
 * @method static int daysInYear(int $year)
 * @method static bool isLeapYear(int $year)
 * @method static array monthNames(?string $language = null)
 * @method static array weekDayNames(?string $language = null)
 * @method static array range()
 * @method static string convert(string $date, string $from = 'ad', string $format = 'Y-m-d')
 *
 * @see NepaliDateFactory
 */
final class NepaliDate extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'nepali-date';
    }
}
