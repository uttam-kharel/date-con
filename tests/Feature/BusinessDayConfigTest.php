<?php

use Sambat\NepaliCalendar\Holidays\HolidayRepository;
use Sambat\NepaliCalendar\NepaliDate;

afterEach(function () {
    HolidayRepository::setInstance(null);
    config()->set('nepali-calendar.weekend', [6]);
    config()->set('nepali-calendar.holidays', []);
});

it('respects a custom weekend from config', function () {
    config()->set('nepali-calendar.weekend', [0, 6]); // Sunday + Saturday

    // 2081-11-11 = Sunday 2025-02-23.
    expect(NepaliDate::parse('2081-11-11')->isWeekend())->toBeTrue();
    expect(NepaliDate::parse('2081-11-11')->isBusinessDay())->toBeFalse();
    expect(NepaliDate::parse('2081-11-09')->isWeekend())->toBeFalse(); // Friday
});

it('reads holidays from the config array', function () {
    config()->set('nepali-calendar.holidays', [
        '2083-01-01' => 'Nepali New Year',
        ['date' => '2083-10-15', 'name' => 'Dashain', 'type' => 'national'],
    ]);

    $date = NepaliDate::parse('2083-01-01');

    expect($date->isHoliday())->toBeTrue();
    expect($date->isBusinessDay())->toBeFalse();
    expect($date->holiday()->name())->toBe('Nepali New Year');
    expect(NepaliDate::parse('2083-10-15')->holiday()->type())->toBe('national');
    expect(NepaliDate::parse('2083-01-02')->isHoliday())->toBeFalse();
});
