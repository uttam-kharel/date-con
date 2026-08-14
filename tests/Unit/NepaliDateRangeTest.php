<?php

use Sambat\NepaliCalendar\Exceptions\InvalidNepaliDateException;
use Sambat\NepaliCalendar\Holidays\HolidayRepository;
use Sambat\NepaliCalendar\NepaliDate;
use Sambat\NepaliCalendar\NepaliDateRange;

afterEach(function () {
    HolidayRepository::setInstance(null);
});

it('builds inclusive ranges and normalizes reversed bounds', function () {
    $range = NepaliDateRange::between('2081-11-05', '2081-11-08');

    expect($range->start()->toDateString())->toBe('2081-11-05');
    expect($range->end()->toDateString())->toBe('2081-11-08');
    expect($range->count())->toBe(4);
    expect($range->isEmpty())->toBeFalse();
    expect($range->toDateStrings())->toBe([
        '2081-11-05', '2081-11-06', '2081-11-07', '2081-11-08',
    ]);

    $reversed = NepaliDateRange::between('2081-11-08', '2081-11-05');
    expect($reversed->start()->toDateString())->toBe('2081-11-05');
    expect($reversed->end()->toDateString())->toBe('2081-11-08');
    expect($reversed->count())->toBe(4);
});

it('accepts NepaliDate instances, Carbon values and date-only semantics', function () {
    $range = NepaliDateRange::between(NepaliDate::parse('2081-11-05'), '2081-11-06');

    expect($range->count())->toBe(2);
    expect($range->start())->toBeInstanceOf(NepaliDate::class);
    expect($range->end())->toBeInstanceOf(NepaliDate::class);
});

it('checks membership', function () {
    $range = NepaliDateRange::between('2081-11-05', '2081-11-08');

    expect($range->contains('2081-11-05'))->toBeTrue();
    expect($range->contains('2081-11-08'))->toBeTrue();
    expect($range->contains('2081-11-06'))->toBeTrue();
    expect($range->contains('2081-11-04'))->toBeFalse();
    expect($range->contains('2081-11-09'))->toBeFalse();
    expect($range->contains(NepaliDate::parse('2081-11-07')))->toBeTrue();
});

it('iterates day by day', function () {
    $range = NepaliDateRange::between('2081-12-30', '2082-01-03');

    $days = [];
    foreach ($range as $date) {
        $days[] = $date->toDateString();
    }

    expect($days)->toBe(['2081-12-30', '2081-12-31', '2082-01-01', '2082-01-02', '2082-01-03']);
});

it('slices into calendar months', function () {
    $range = NepaliDateRange::between('2081-11-28', '2082-01-03');
    $months = $range->months();

    expect($months)->toHaveCount(3);
    expect($months[0]->start()->toDateString())->toBe('2081-11-28');
    expect($months[0]->end()->toDateString())->toBe('2081-11-29');
    expect($months[1]->start()->toDateString())->toBe('2081-12-01');
    expect($months[1]->end()->toDateString())->toBe('2081-12-31');
    expect($months[2]->start()->toDateString())->toBe('2082-01-01');
    expect($months[2]->end()->toDateString())->toBe('2082-01-03');
});

it('slices into BS years', function () {
    $range = NepaliDateRange::between('2081-12-30', '2082-01-02');
    $years = $range->years();

    expect($years)->toHaveCount(2);
    expect($years[0]->start()->toDateString())->toBe('2081-12-30');
    expect($years[0]->end()->toDateString())->toBe('2081-12-31');
    expect($years[1]->start()->toDateString())->toBe('2082-01-01');
    expect($years[1]->end()->toDateString())->toBe('2082-01-02');
});

it('slices into calendar weeks', function () {
    // 2081-11-05 = Monday 2025-02-17; a Sunday-first week starts 2081-11-04.
    $range = NepaliDateRange::between('2081-11-05', '2081-11-12');
    $weeks = $range->weeks();

    expect($weeks)->toHaveCount(2);
    expect($weeks[0]->start()->toDateString())->toBe('2081-11-05');
    expect($weeks[0]->end()->toDateString())->toBe('2081-11-10');
    expect($weeks[1]->start()->toDateString())->toBe('2081-11-11');
    expect($weeks[1]->end()->toDateString())->toBe('2081-11-12');
});

it('serializes ranges', function () {
    $range = NepaliDateRange::between('2081-11-05', '2081-11-06');

    $array = $range->toArray();
    expect($array['start'])->toBe('2081-11-05');
    expect($array['end'])->toBe('2081-11-06');
    expect($array['days'])->toBe(2);

    expect(json_decode($range->toJson(), true))->toBe($array);
    expect((string) $range)->toBe('2081-11-05 to 2081-11-06');
});

it('builds ranges from a date with rangeTo', function () {
    $range = NepaliDate::parse('2081-11-05')->rangeTo('2081-11-07');

    expect($range)->toBeInstanceOf(NepaliDateRange::class);
    expect($range->count())->toBe(3);
});

it('detects containment, overlap and adjacency', function () {
    $a = NepaliDateRange::between('2081-11-01', '2081-11-10');
    $b = NepaliDateRange::between('2081-11-05', '2081-11-15');
    $c = NepaliDateRange::between('2081-11-11', '2081-11-20');
    $d = NepaliDateRange::between('2081-12-01', '2081-12-10');

    expect($a->containsRange(NepaliDateRange::between('2081-11-03', '2081-11-08')))->toBeTrue();
    expect($a->containsRange($b))->toBeFalse();
    expect($a->overlaps($b))->toBeTrue();
    expect($a->overlaps($d))->toBeFalse();
    expect($a->touches($c))->toBeTrue(); // 11-10 .. 11-11 adjacent
    expect($a->touches($b))->toBeFalse();
    expect($a->touches($d))->toBeFalse();
});

it('merges, intersects and measures gaps between ranges', function () {
    $a = NepaliDateRange::between('2081-11-01', '2081-11-10');
    $b = NepaliDateRange::between('2081-11-05', '2081-11-15');
    $c = NepaliDateRange::between('2081-11-11', '2081-11-20');
    $d = NepaliDateRange::between('2081-12-01', '2081-12-10');

    expect($a->merge($b)->toArray())->toBe(NepaliDateRange::between('2081-11-01', '2081-11-15')->toArray());
    expect($a->merge($c)->toArray())->toBe(NepaliDateRange::between('2081-11-01', '2081-11-20')->toArray());
    expect($a->merge($d))->toBeNull();

    expect($a->intersection($b)->toDateStrings())->toBe(['2081-11-05', '2081-11-06', '2081-11-07', '2081-11-08', '2081-11-09', '2081-11-10']);
    expect($a->intersection($d))->toBeNull();

    $gap = $a->gap($d);
    expect($gap->start()->toDateString())->toBe('2081-11-11');
    expect($gap->end()->toDateString())->toBe('2081-11-29'); // Falgun 2081 has 29 days
    expect($gap->count())->toBe(19);
    expect($a->gap($c))->toBeNull(); // adjacent, no gap
});

it('samples every N-th day', function () {
    $range = NepaliDateRange::between('2081-11-01', '2081-11-10');

    expect($range->daysEvery(3))->toHaveCount(4);
    expect(array_map(fn ($d) => $d->toDateString(), $range->daysEvery(3)))
        ->toBe(['2081-11-01', '2081-11-04', '2081-11-07', '2081-11-10']);
    expect(fn () => $range->daysEvery(0))->toThrow(InvalidNepaliDateException::class);
});

it('counts business days, weekends and holidays inside a range', function () {
    HolidayRepository::setInstance(HolidayRepository::fromArray([
        '2081-11-07' => 'Test Holiday', // Wednesday
    ]));

    // Mon 05 .. Sun 11 (Sat 10 is the weekend).
    $range = NepaliDateRange::between('2081-11-05', '2081-11-11');

    expect($range->businessDayCount())->toBe(5); // Tue, Wed holiday skipped, Thu, Fri, Sun
    expect($range->businessDays())->toHaveCount(5);
    expect($range->workingDays())->toHaveCount(5);
    expect($range->weekends())->toHaveCount(1);
    expect($range->weekends()[0]->toDateString())->toBe('2081-11-10');
    expect($range->holidays())->toHaveCount(1);
    expect($range->holidays()[0]->toDateString())->toBe('2081-11-07');
});
