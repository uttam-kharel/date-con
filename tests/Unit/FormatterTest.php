<?php

use Sambat\NepaliCalendar\NepaliDate;

it('formats basic date tokens', function () {
    $date = NepaliDate::parse('2081-11-05');

    expect($date->format('Y-m-d', null, false))->toBe('2081-11-05');
    expect($date->format('Y/m/d', null, false))->toBe('2081/11/05');
    expect($date->format('Y-n-j', null, false))->toBe('2081-11-5');
    expect($date->format('y', null, false))->toBe('81');
});

it('formats names in Nepali, romanized and English', function () {
    $date = NepaliDate::parse('2081-11-05'); // Monday 2025-02-17

    expect($date->format('l, F j, Y', 'nepali', false))->toBe('सोमबार, फागुन 5, 2081');
    expect($date->format('l, F j, Y', 'roman', false))->toBe('Sombaar, Falgun 5, 2081');
    expect($date->format('l, F j, Y', 'english', false))->toBe('Monday, February 17, 2025');
    expect($date->format('D, M j', 'roman', false))->toBe('Som, Fal 5');
});

it('renders Devanagari numerals', function () {
    $date = NepaliDate::parse('2081-11-05');

    expect($date->format('Y-m-d'))->toBe('२०८१-११-०५');
    expect($date->format('d/m/Y'))->toBe('०५/११/२०८१');
    expect($date->toNepaliNumerals())->toBe('२०८१-११-०५');
    expect($date->toEnglishNumerals())->toBe('2081-11-05');
});

it('supports ordinal suffixes and escapes', function () {
    $date = NepaliDate::parse('2081-11-05');

    expect($date->format('jS F', 'roman', false))->toBe('5th Falgun');
    expect($date->format('Y \y\e\a\r', null, false))->toBe('2081 year');
    expect($date->format('jS', null, false))->toBe('5th');
});

it('formats BS-specific calendar tokens', function () {
    $date = NepaliDate::parse('2081-11-05');

    expect($date->format('z', null, false))->toBe('310'); // day of year, 0-based
    expect($date->format('t', null, false))->toBe('29');  // days in month
    expect($date->format('L', null, false))->toBe('1');   // leap year
    expect($date->format('N', null, false))->toBe('1');   // ISO weekday (Monday)
    expect($date->format('w', null, false))->toBe('1');   // 0=Sunday .. 6=Saturday
    expect($date->format('W', null, false))->toBe('45');  // week of BS year
});

it('formats time tokens from the AD instant', function () {
    $date = NepaliDate::parse('2081-11-05 14:30:45');

    expect($date->format('H:i:s', null, false))->toBe('14:30:45');
    expect($date->format('h:i A', null, false))->toBe('02:30 PM');
    expect($date->format('g:i a', null, false))->toBe('2:30 pm');
});

it('formats accessor names directly', function () {
    $date = NepaliDate::parse('2081-11-05');

    expect($date->weekDayName())->toBe('सोमबार');
    expect($date->weekDayName('roman'))->toBe('Sombaar');
    expect($date->weekDayName('english'))->toBe('Monday');
    expect($date->weekDayShort('roman'))->toBe('Som');
    expect($date->monthName())->toBe('फागुन');
    expect($date->monthName('roman'))->toBe('Falgun');
    expect($date->monthName('english'))->toBe('February');
    expect($date->monthShortName('roman'))->toBe('Fal');
});
