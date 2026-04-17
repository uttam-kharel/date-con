<?php

use Sambat\NepaliCalendar\Calendar;
use Sambat\NepaliCalendar\Exceptions\InvalidNepaliDateException;
use Sambat\NepaliCalendar\NepaliDate;
use Sambat\NepaliCalendar\NepaliFiscalYear;

it('derives the fiscal year from a date', function () {
    $fy = NepaliFiscalYear::fromDate('2083-04-15');

    expect($fy->year())->toBe(2083);
    expect($fy->label())->toBe('2083/84');
    expect($fy->startDate()->toDateString())->toBe('2083-04-01');
    expect($fy->endDate()->toDateString())->toBe(NepaliDate::parse('2084-03-01')->endOfMonth()->toDateString());

    // Chaitra (month 3) of 2083 still belongs to fiscal year 2082/83.
    expect(NepaliFiscalYear::fromDate('2083-03-15')->label())->toBe('2082/83');
    expect(NepaliFiscalYear::forYear(2083)->label())->toBe('2083/84');
});

it('knows its own total days', function () {
    $fy = NepaliFiscalYear::forYear(2083);
    $expected = 0;

    for ($month = 4; $month <= 12; $month++) {
        $expected += Calendar::daysInBsMonth(2083, $month);
    }
    for ($month = 1; $month <= 3; $month++) {
        $expected += Calendar::daysInBsMonth(2084, $month);
    }

    expect($fy->days())->toBe($expected);
});

it('checks membership', function () {
    $fy = NepaliFiscalYear::forYear(2083);

    expect($fy->contains('2083-04-01'))->toBeTrue();
    expect($fy->contains(NepaliDate::parse('2084-03-31')))->toBeTrue();
    expect($fy->contains('2083-03-31'))->toBeFalse();
    expect($fy->contains('2085-01-01'))->toBeFalse();
});

it('computes fiscal quarters', function () {
    $fy = NepaliFiscalYear::forYear(2083);

    expect($fy->quarter(NepaliDate::parse('2083-04-15')))->toBe(1);
    expect($fy->quarter(NepaliDate::parse('2083-07-15')))->toBe(2);
    expect($fy->quarter(NepaliDate::parse('2083-10-15')))->toBe(3);
    expect($fy->quarter(NepaliDate::parse('2084-01-15')))->toBe(4);

    expect(fn () => $fy->quarter(NepaliDate::parse('2082-03-15')))->toThrow(InvalidNepaliDateException::class);
});

it('builds fiscal quarter ranges', function () {
    $fy = NepaliFiscalYear::forYear(2083);

    $q1 = $fy->quarterRange(1);
    expect($q1->start()->toDateString())->toBe('2083-04-01');
    expect($q1->end()->toDateString())->toBe(NepaliDate::parse('2083-06-01')->endOfMonth()->toDateString());

    $q4 = $fy->quarterRange(4);
    expect($q4->start()->toDateString())->toBe('2084-01-01');
    expect($q4->end()->toDateString())->toBe(NepaliDate::parse('2084-03-01')->endOfMonth()->toDateString());

    expect($fy->quarters())->toHaveCount(4);
    expect($fy->quarters()[0]['label'])->toBe('2083/84 Q1');
    expect($fy->quarters()[3]['number'])->toBe(4);

    expect(fn () => $fy->quarterRange(5))->toThrow(InvalidNepaliDateException::class);
});

it('detects the current fiscal year', function () {
    expect(NepaliFiscalYear::fromDate(NepaliDate::now())->isCurrent())->toBeTrue();
    expect(NepaliFiscalYear::forYear(2000)->isCurrent())->toBeFalse();
});

it('serializes fiscal years', function () {
    $array = NepaliFiscalYear::forYear(2083)->toArray();

    expect($array['label'])->toBe('2083/84');
    expect($array['start'])->toBe('2083-04-01');
    expect($array['quarters'])->toHaveCount(4);
    expect(json_decode(NepaliFiscalYear::forYear(2083)->toJson(), true))->toBe($array);
    expect((string) NepaliFiscalYear::forYear(2083))->toBe('2083/84');
});
