<?php

use Sambat\NepaliCalendar\Holidays\HolidayCollection;
use Sambat\NepaliCalendar\Holidays\HolidayRepository;
use Sambat\NepaliCalendar\Holidays\NepaliHoliday;
use Sambat\NepaliCalendar\NepaliDate;

afterEach(function () {
    HolidayRepository::setInstance(null);
});

it('detects weekends by the default Nepal convention (Saturday)', function () {
    // 2081-11-05 = Monday 2025-02-17.
    expect(NepaliDate::parse('2081-11-05')->isWeekend())->toBeFalse();
    expect(NepaliDate::parse('2081-11-10')->isWeekend())->toBeTrue(); // Saturday 2025-02-22
    expect(NepaliDate::parse('2081-11-11')->isWeekend())->toBeFalse(); // Sunday
});

it('adds and subtracts business days, skipping Saturday', function () {
    $monday = NepaliDate::parse('2081-11-05');

    expect($monday->addBusinessDays(1)->toDateString())->toBe('2081-11-06');
    expect($monday->addBusinessDays(5)->toDateString())->toBe('2081-11-11'); // skips Sat 11-10
    expect($monday->addBusinessDays(6)->toDateString())->toBe('2081-11-12');
    expect($monday->subBusinessDays(1)->toDateString())->toBe('2081-11-04');
    expect($monday->nextBusinessDay()->toDateString())->toBe('2081-11-06');
    expect($monday->previousBusinessDay()->toDateString())->toBe('2081-11-04');
    expect($monday->addBusinessDays(0)->equals($monday))->toBeTrue();
});

it('counts business days between dates (signed)', function () {
    $start = NepaliDate::parse('2081-11-05');

    expect($start->businessDaysUntil('2081-11-12'))->toBe(6);
    expect($start->businessDaysUntil('2081-11-11'))->toBe(5); // Sunday counts
    expect(NepaliDate::parse('2081-11-12')->businessDaysUntil('2081-11-05'))->toBe(-6);
    expect($start->businessDaysUntil('2081-11-05'))->toBe(0);
});

it('parses holidays from all config forms', function () {
    $repository = HolidayRepository::fromArray([
        '2083-01-01' => 'Nepali New Year',
        ['date' => '2083-10-15', 'name' => 'Dashain', 'type' => 'national'],
        '2083-05-15',
    ]);

    expect($repository->all()->count())->toBe(3);
    expect($repository->forYear(2083)->count())->toBe(3);
    expect($repository->forYear(2084)->count())->toBe(0);

    $newYear = $repository->forDate(NepaliDate::parse('2083-01-01'));
    expect($newYear)->toBeInstanceOf(NepaliHoliday::class);
    expect($newYear->name())->toBe('Nepali New Year');
    expect($newYear->type())->toBe('custom');

    $dashain = $repository->forDate(NepaliDate::parse('2083-10-15'));
    expect($dashain->name())->toBe('Dashain');
    expect($dashain->type())->toBe('national');

    $plain = $repository->forDate(NepaliDate::parse('2083-05-15'));
    expect($plain->name())->toBe('');
    expect($plain->date()->toDateString())->toBe('2083-05-15');

    expect($repository->forDate(NepaliDate::parse('2083-01-02')))->toBeNull();
});

it('marks holidays and non-business days', function () {
    HolidayRepository::setInstance(HolidayRepository::fromArray([
        '2081-11-07' => 'Test Holiday',
    ]));

    $holiday = NepaliDate::parse('2081-11-07');

    expect($holiday->isHoliday())->toBeTrue();
    expect($holiday->holiday())->toBeInstanceOf(NepaliHoliday::class);
    expect($holiday->holiday()->name())->toBe('Test Holiday');
    expect($holiday->isBusinessDay())->toBeFalse();
    expect($holiday->isWorkingDay())->toBeFalse();
    expect(NepaliDate::parse('2081-11-06')->isHoliday())->toBeFalse();
});

it('skips holidays in business-day arithmetic', function () {
    HolidayRepository::setInstance(HolidayRepository::fromArray([
        '2081-11-07' => 'Test Holiday',
    ]));

    // Tue 11-06 (1), Wed 11-07 is a holiday (skipped), Thu 11-08 (2).
    expect(NepaliDate::parse('2081-11-05')->addBusinessDays(2)->toDateString())->toBe('2081-11-08');
    expect(NepaliDate::parse('2081-11-05')->businessDaysUntil('2081-11-12'))->toBe(5);
});

it('serializes holidays and collections', function () {
    $holiday = new NepaliHoliday(NepaliDate::parse('2083-10-15'), 'Dashain', 'national');

    expect($holiday->toArray())->toBe([
        'date' => '2083-10-15',
        'name' => 'Dashain',
        'type' => 'national',
    ]);
    expect(json_decode($holiday->toJson(), true))->toBe($holiday->toArray());
    expect((string) $holiday)->toBe('Dashain');

    $collection = new HolidayCollection([$holiday]);
    expect($collection->count())->toBe(1);
    expect($collection->names())->toBe(['Dashain']);
    expect($collection->dates())->toBe(['2083-10-15']);
    expect($collection->isEmpty())->toBeFalse();
    expect(json_decode($collection->toJson(), true))->toBe($collection->toArray());
});
