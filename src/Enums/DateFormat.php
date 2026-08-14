<?php

declare(strict_types=1);

namespace Sambat\NepaliCalendar\Enums;

/**
 * Named formatting presets for BS dates.
 *
 * The same token language as PHP's date() applies, so names and numerals
 * follow the configured language / numeral settings (Devanagari by default):
 *
 *     $date->formatPreset(DateFormat::FULL);          // सोमबार, फागुन ५, २०८१
 *     $date->formatPreset(DateFormat::FULL, 'english'); // Monday, February 17, 2025
 */
enum DateFormat: string
{
    case SHORT = 'Y-m-d';
    case MEDIUM = 'Y M d';
    case LONG = 'F j, Y';
    case FULL = 'l, F j, Y';

    case DATETIME_SHORT = 'Y-m-d H:i';
    case DATETIME_MEDIUM = 'Y M d H:i';
    case DATETIME_LONG = 'F d, Y H:i:s';
    case DATETIME_FULL = 'l, F j, Y H:i:s';

    case TIME_SHORT = 'H:i';
    case TIME_MEDIUM = 'H:i:s';
    case TIME_FULL = 'g:i:s A';
}
