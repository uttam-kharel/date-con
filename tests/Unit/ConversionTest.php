<?php

use Carbon\Carbon;
use Sambat\NepaliCalendar\Calendar;
use Sambat\NepaliCalendar\Exceptions\InvalidNepaliDateException;
use Sambat\NepaliCalendar\Exceptions\NepaliDateOutOfRangeException;
use Sambat\NepaliCalendar\NepaliDate;

it('converts known AD dates to BS', function (string $ad, string $bs) {
    expect(NepaliDate::fromAd($ad)->toDateString())->toBe($bs);
})->with([
    ['1943-04-14', '2000-01-01'],
    ['2023-04-14', '2080-01-01'],
    ['2024-04-13', '2081-01-01'],
    ['2025-04-14', '2082-01-01'],
    ['2026-04-14', '2083-01-01'],
    ['2024-12-16', '2081-09-01'],
    ['2025-01-16', '2081-10-03'],
    ['2025-02-17', '2081-11-05'],
    ['2025-03-12', '2081-11-28'],
    ['2025-06-30', '2082-03-16'],
    ['2043-04-13', '2099-12-30'],
]);

it('converts known BS dates to AD', function (string $bs, string $ad) {
    expect(NepaliDate::parse($bs)->ad()->format('Y-m-d'))->toBe($ad);
})->with([
    ['2000-01-01', '1943-04-14'],
    ['2080-01-01', '2023-04-14'],
    ['2081-01-01', '2024-04-13'],
    ['2082-01-01', '2025-04-14'],
    ['2083-01-01', '2026-04-14'],
    ['2081-09-01', '2024-12-16'],
    ['2081-10-03', '2025-01-16'],
    ['2081-11-05', '2025-02-17'],
    ['2081-11-28', '2025-03-12'],
    ['2082-03-16', '2025-06-30'],
    ['2099-12-30', '2043-04-13'],
]);

it('round-trips every sampled BS day through the AD calendar', function () {
    for ($epoch = 0; $epoch < Calendar::totalDays(); $epoch += 17) {
        $bs = Calendar::fromEpochDay($epoch);
        $ad = Calendar::bsToAd($bs['year'], $bs['month'], $bs['day']);
        $back = Calendar::adToBs($ad['year'], $ad['month'], $ad['day']);

        expect($back)->toBe($bs);
    }
});

it('keeps AD weekdays consistent with BS dates', function () {
    // 2025-02-17 is a Monday; 2024-04-13 is a Saturday.
    expect(NepaliDate::parse('2081-11-05')->weekDay())->toBe(2);
    expect(NepaliDate::parse('2081-11-05')->weekDayIso())->toBe(1);
    expect(NepaliDate::parse('2081-01-01')->weekDay())->toBe(7);
    expect(NepaliDate::parse('2000-01-01')->weekDay())->toBe(4);
});

it('returns the correct day counts and leap years', function () {
    expect(Calendar::daysInBsMonth(2081, 11))->toBe(29);   // Falgun
    expect(Calendar::daysInBsMonth(2081, 12))->toBe(31);   // Chaitra
    expect(Calendar::daysInBsMonth(2080, 1))->toBe(31);    // Baisakh
    expect(Calendar::daysInBsMonth(2088, 1))->toBe(30);    // Baisakh

    expect(Calendar::daysInBsYear(2080))->toBe(365);
    expect(Calendar::daysInBsYear(2081))->toBe(366);
    expect(Calendar::daysInBsYear(2085))->toBe(366);

    expect(Calendar::isBsLeapYear(2081))->toBeTrue();
    expect(Calendar::isBsLeapYear(2080))->toBeFalse();
});

it('rejects dates outside the supported range', function () {
    expect(fn () => NepaliDate::fromAd('1943-04-13'))->toThrow(NepaliDateOutOfRangeException::class);
    expect(fn () => NepaliDate::fromAd('2043-04-14'))->toThrow(NepaliDateOutOfRangeException::class);
    expect(fn () => NepaliDate::parse('2099-12-31'))->toThrow(InvalidNepaliDateException::class);
    expect(fn () => NepaliDate::parse('2100-01-01'))->toThrow(NepaliDateOutOfRangeException::class);
    expect(fn () => NepaliDate::parse('1999-12-30'))->toThrow(NepaliDateOutOfRangeException::class);
});

it('rejects impossible dates', function () {
    expect(fn () => NepaliDate::parse('2081-13-01'))->toThrow(InvalidNepaliDateException::class);
    expect(fn () => NepaliDate::parse('2081-00-10'))->toThrow(InvalidNepaliDateException::class);
    expect(fn () => NepaliDate::parse('2081-02-33'))->toThrow(InvalidNepaliDateException::class);
    expect(fn () => NepaliDate::parse('not-a-date'))->toThrow(InvalidNepaliDateException::class);
    expect(fn () => NepaliDate::fromAd('2025-02-31'))->toThrow(InvalidNepaliDateException::class);
});

it('exposes the supported ranges', function () {
    $ad = Calendar::adRange();

    expect(sprintf('%04d-%02d-%02d', $ad['min']['year'], $ad['min']['month'], $ad['min']['day']))->toBe('1943-04-14');
    expect(sprintf('%04d-%02d-%02d', $ad['max']['year'], $ad['max']['month'], $ad['max']['day']))->toBe('2043-04-13');
    expect(Calendar::totalDays())->toBe(36525);
});

it('parses timestamps and Carbon instances as AD instants', function () {
    $carbon = Carbon::create(2025, 3, 12, 10, 30);

    expect(NepaliDate::fromCarbon($carbon)->toDateString())->toBe('2081-11-28');
    expect(NepaliDate::fromTimestamp($carbon->timestamp)->toDateString())->toBe('2081-11-28');
    expect(NepaliDate::fromCarbon($carbon)->ad()->format('H:i'))->toBe('10:30');
});
