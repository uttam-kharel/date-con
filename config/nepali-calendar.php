<?php

return [

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

];
