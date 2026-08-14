<?php

use Sambat\NepaliCalendar\NepaliDate;

it('moves to tomorrow and yesterday', function () {
    expect(NepaliDate::parse('2081-11-05')->tomorrow()->toDateString())->toBe('2081-11-06');
    expect(NepaliDate::parse('2081-11-05')->yesterday()->toDateString())->toBe('2081-11-04');
    expect(NepaliDate::parse('2081-12-31')->tomorrow()->toDateString())->toBe('2082-01-01');
    expect(NepaliDate::parse('2081-01-01')->yesterday()->toDateString())->toBe('2080-12-30'); // Chaitra 2080 has 30 days
});

it('detects tomorrow and yesterday relative to now', function () {
    expect(NepaliDate::now()->tomorrow()->isTomorrow())->toBeTrue();
    expect(NepaliDate::now()->yesterday()->isYesterday())->toBeTrue();
    expect(NepaliDate::now()->isTomorrow())->toBeFalse();
    expect(NepaliDate::now()->isYesterday())->toBeFalse();
});

it('detects weekdays and moves between weekdays', function () {
    // 2081-11-05 = Monday, 2081-11-08 = Thursday, 2081-11-10 = Saturday.
    expect(NepaliDate::parse('2081-11-05')->isWeekday())->toBeTrue();
    expect(NepaliDate::parse('2081-11-10')->isWeekday())->toBeFalse();

    expect(NepaliDate::parse('2081-11-08')->nextWeekday()->toDateString())->toBe('2081-11-09'); // Fri
    expect(NepaliDate::parse('2081-11-08')->previousWeekday()->toDateString())->toBe('2081-11-07'); // Wed
    expect(NepaliDate::parse('2081-11-10')->nextWeekday()->toDateString())->toBe('2081-11-11'); // Sun
    expect(NepaliDate::parse('2081-11-10')->previousWeekday()->toDateString())->toBe('2081-11-09'); // Fri
});

it('computes time differences from the AD instants', function () {
    $a = NepaliDate::parse('2081-11-05 10:00:00');
    $b = NepaliDate::parse('2081-11-05 12:30:45');

    expect($a->diffInSeconds($b))->toBe(9045);
    expect($a->diffInMinutes($b))->toBe(150);
    expect($a->diffInHours($b))->toBe(2);

    expect($b->diffInMinutes($a, absolute: false))->toBe(-150);
    expect($b->diffInHours($a))->toBe(2);

    expect(NepaliDate::parse('2081-11-05')->diffInHours(NepaliDate::parse('2081-11-06')))->toBe(24);
});

it('renders the same date in both scripts', function () {
    $date = NepaliDate::parse('2081-11-05'); // Monday 2025-02-17

    expect($date->formatBoth())->toBe([
        'nepali' => 'सोमबार, फागुन ५, २०८१',
        'english' => 'Monday, February 17, 2025',
    ]);

    // The English side renders the same instant in the Gregorian calendar.
    expect($date->formatBoth('Y-m-d'))->toBe([
        'nepali' => '२०८१-११-०५',
        'english' => '2025-02-17',
    ]);
});
