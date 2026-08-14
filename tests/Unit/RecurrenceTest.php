<?php

use Sambat\NepaliCalendar\Exceptions\InvalidNepaliDateException;
use Sambat\NepaliCalendar\NepaliDate;
use Sambat\NepaliCalendar\Recurrence;

it('builds daily recurrences', function () {
    expect(Recurrence::daily('2081-11-05')->take(3)->toDateStrings())
        ->toBe(['2081-11-05', '2081-11-06', '2081-11-07']);
    expect(Recurrence::daily('2081-11-05')->every(2)->take(3)->toDateStrings())
        ->toBe(['2081-11-05', '2081-11-07', '2081-11-09']);
});

it('bounds recurrences with until and between', function () {
    expect(Recurrence::daily('2081-11-05')->until('2081-11-08')->dates())->toHaveCount(4);
    expect(Recurrence::daily()->between('2081-11-05', '2081-11-07')->toDateStrings())
        ->toBe(['2081-11-05', '2081-11-06', '2081-11-07']);
    expect(Recurrence::daily('2081-11-05')->take(2)->count())->toBe(2);
});

it('builds weekly recurrences', function () {
    expect(Recurrence::weekly('2081-11-05')->take(2)->toDateStrings())
        ->toBe(['2081-11-05', '2081-11-12']); // every Monday
    // Falgun 2081 has 29 days, so the third Monday lands on 12-04.
    expect(Recurrence::weekly('2081-11-05')->every(2)->take(3)->toDateStrings())
        ->toBe(['2081-11-05', '2081-11-19', '2081-12-04']);
});

it('restricts weekly recurrences to specific weekdays', function () {
    // 2081-11-05 = Monday; Fridays are 11-09 and 11-16.
    $dates = Recurrence::weekly('2081-11-05')->on('monday', 'friday')->until('2081-11-20')->toDateStrings();

    expect($dates)->toBe(['2081-11-05', '2081-11-09', '2081-11-12', '2081-11-16', '2081-11-19']);

    // Numeric weekdays work too: 2 = Monday, 6 = Friday.
    expect(Recurrence::weekly('2081-11-05')->on(2, 6)->until('2081-11-20')->toDateStrings())->toBe($dates);
});

it('builds monthly and yearly recurrences', function () {
    expect(Recurrence::monthly('2081-11-05')->take(3)->toDateStrings())
        ->toBe(['2081-11-05', '2081-12-05', '2082-01-05']);
    expect(Recurrence::monthly('2081-11-05')->every(2)->take(2)->toDateStrings())
        ->toBe(['2081-11-05', '2082-01-05']);
    expect(Recurrence::yearly('2081-11-05')->take(2)->toDateStrings())
        ->toBe(['2081-11-05', '2082-11-05']);
});

it('iterates and serializes recurrences', function () {
    $recurrence = Recurrence::daily('2081-11-05')->take(3);

    $collected = [];
    foreach ($recurrence as $date) {
        $collected[] = $date;
    }

    expect($collected)->toHaveCount(3);
    expect($collected[0])->toBeInstanceOf(NepaliDate::class);
    expect($recurrence->toArray()['frequency'])->toBe('daily');
    expect(json_decode($recurrence->toJson(), true)['dates'])->toBe(['2081-11-05', '2081-11-06', '2081-11-07']);
});

it('guards against unbounded rules', function () {
    expect(fn () => Recurrence::daily('2000-01-01')->dates())
        ->toThrow(InvalidNepaliDateException::class, 'more than '.Recurrence::MAX_OCCURRENCES.' occurrences');
});

it('validates rule configuration', function () {
    expect(fn () => Recurrence::daily()->every(0))->toThrow(InvalidNepaliDateException::class);
    expect(fn () => Recurrence::daily()->take(0))->toThrow(InvalidNepaliDateException::class);
    expect(fn () => Recurrence::daily()->on('monday'))->toThrow(InvalidNepaliDateException::class);
    expect(fn () => Recurrence::weekly()->on('bogus'))->toThrow(InvalidNepaliDateException::class);
    expect(fn () => Recurrence::weekly()->on(9))->toThrow(InvalidNepaliDateException::class);
});
