<?php

declare(strict_types=1);

use Sambat\NepaliCalendar\Exceptions\InvalidNepaliDateException;
use Sambat\NepaliCalendar\NepaliDate;

it('parses Y-m-d numeric formats', function (): void {
    $date = NepaliDate::createFromFormat('Y-m-d', '2083-04-29');

    expect($date->year())->toBe(2083)
        ->and($date->month())->toBe(4)
        ->and($date->day())->toBe(29);
});

it('round-trips format() output', function (string $format, string $input): void {
    $date = NepaliDate::createFromFormat($format, $input);

    expect($date->format($format, 'nepali', false))->toBe($input);
})->with([
    ['Y-m-d', '2083-04-29'],
    ['Y/m/d', '2083/04/29'],
    ['d-m-Y', '29-04-2083'],
    ['Y n j', '2083 4 29'],
    ['j F Y', '29 साउन 2083'],
    ['d M Y', '29 साउन 2083'],
]);

it('round-trips romanized month names', function (): void {
    $date = NepaliDate::createFromFormat('j F Y', '29 Shrawan 2083');

    expect($date->format('j F Y', 'roman', false))->toBe('29 Shrawan 2083');
});

it('accepts Devanagari numerals', function (): void {
    $date = NepaliDate::createFromFormat('Y-m-d', '२०८३-०४-२९');

    expect($date->year())->toBe(2083)
        ->and($date->month())->toBe(4)
        ->and($date->day())->toBe(29);
});

it('accepts Devanagari month names', function (): void {
    $date = NepaliDate::createFromFormat('j F Y', '२९ साउन २०८३');

    expect($date->month())->toBe(4)
        ->and($date->day())->toBe(29)
        ->and($date->year())->toBe(2083);
});

it('matches literal characters', function (): void {
    $date = NepaliDate::createFromFormat('Y.m.d', '2083.04.29');

    expect($date->toDateString())->toBe('2083-04-29');
});

it('supports two-digit years', function (): void {
    $date = NepaliDate::createFromFormat('y-m-d', '83-04-29');

    expect($date->year())->toBe(2083);
});

it('rejects values that do not match the format', function (): void {
    NepaliDate::createFromFormat('Y-m-d', '2083/04/29');
})->throws(InvalidNepaliDateException::class);

it('rejects impossible dates', function (): void {
    NepaliDate::createFromFormat('Y-m-d', '2083-04-33');
})->throws(InvalidNepaliDateException::class);

it('detects the same week', function (): void {
    $a = NepaliDate::fromBs(2083, 4, 1);
    $b = NepaliDate::fromBs(2083, 4, 3);

    expect($a->isSameWeek($b))->toBeTrue()
        ->and($a->isSameWeek(NepaliDate::fromBs(2083, 4, 8)))->toBeFalse()
        ->and($a->isSameWeek(NepaliDate::fromBs(2084, 4, 1)))->toBeFalse();
});

it('detects the same quarter', function (): void {
    $a = NepaliDate::fromBs(2083, 1, 15); // Baisakh — Q1
    $b = NepaliDate::fromBs(2083, 3, 30); // Ashadh — Q1

    expect($a->isSameQuarter($b))->toBeTrue()
        ->and($a->isSameQuarter(NepaliDate::fromBs(2083, 4, 1)))->toBeFalse()
        ->and($a->isSameQuarter(NepaliDate::fromBs(2084, 2, 1)))->toBeFalse();
});

it('detects the same fiscal year', function (): void {
    $shrawan = NepaliDate::fromBs(2083, 4, 1);   // start of FY 2083/84
    $ashadh = NepaliDate::fromBs(2084, 3, 30);   // end of FY 2083/84

    expect($shrawan->isSameFiscalYear($ashadh))->toBeTrue()
        ->and($shrawan->isSameFiscalYear(NepaliDate::fromBs(2083, 3, 30)))->toBeFalse();
});
