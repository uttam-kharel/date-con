<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Calendar data driver
    |--------------------------------------------------------------------------
    |
    | Where the BS month-length table comes from.
    |
    |   'algorithm' - the built-in, observation-verified constant table
    |                 (no database required, works in plain PHP)
    |
    |   'database'  - a `nepali_calendar_years` table in your own database.
    |                 Publish + run the package migrations, then seed the
    |                 table with `php artisan nepali:seed`.
    |
    | For fully custom sources (API, files, ...) bind your own
    | CalendarDataProvider in the container as 'nepali-calendar.provider'.
    |
    */

    'driver' => env('NEPALI_CALENDAR_DRIVER', 'algorithm'),

    /*
    |--------------------------------------------------------------------------
    | Database table used by the 'database' driver
    |--------------------------------------------------------------------------
    |
    */

    'database_table' => env('NEPALI_CALENDAR_TABLE', 'nepali_calendar_years'),

    /*
    |--------------------------------------------------------------------------
    | Default language for month / weekday names
    |--------------------------------------------------------------------------
    |
    | Controls the names returned by format() tokens (F, M, l, D) and
    | toArray() when no language is explicitly given.
    |
    | Supported: 'nepali' (Devanagari), 'roman' (Latin transliteration),
    |            'english' (Gregorian names of the equivalent AD date)
    |
    */

    'language' => 'nepali',

    /*
    |--------------------------------------------------------------------------
    | Default numeral script
    |--------------------------------------------------------------------------
    |
    | When true, every digit rendered by format() is written in Devanagari
    | script (२०८१-११-०५). Set to 'english' to keep western digits.
    |
    */

    'numerals' => 'devanagari',

    /*
    |--------------------------------------------------------------------------
    | First day of the week
    |--------------------------------------------------------------------------
    |
    | Used by startOfWeek() / endOfWeek() and calendar() grids.
    |
    | Supported: 'sunday', 'monday'
    |
    */

    'week_starts_on' => 'sunday',

    /*
    |--------------------------------------------------------------------------
    | Default output format
    |--------------------------------------------------------------------------
    |
    | Used by __toString() and any method that formats without an explicit
    | format argument.
    |
    */

    'default_format' => 'Y-m-d',

    /*
    |--------------------------------------------------------------------------
    | Weekend days
    |--------------------------------------------------------------------------
    |
    | Days treated as non-working by isWeekend() / isBusinessDay() and the
    | business-day arithmetic. Uses PHP's weekday numbers:
    | 0 = Sunday ... 6 = Saturday. Nepal's standard weekend is Saturday.
    |
    */

    'weekend' => [6],

    /*
    |--------------------------------------------------------------------------
    | Holidays
    |--------------------------------------------------------------------------
    |
    | Fixed public holidays used by isHoliday() / isBusinessDay(). Each entry
    | can be a BS date string, a `'YYYY-MM-DD' => 'Holiday name'` pair, or
    | ['date' => ..., 'name' => ..., 'type' => ...]. The core never hardcodes
    | festive dates — supply your own, or bind 'nepali-calendar.holidays'
    | in the container for a fully custom source.
    |
    | 'holidays' => [
    |     '2083-01-01' => 'Nepali New Year',
    |     ['date' => '2083-10-15', 'name' => 'Dashain', 'type' => 'national'],
    | ],
    |
    */

    'holidays' => [],

];
