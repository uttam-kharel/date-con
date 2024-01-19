<?php

use Carbon\Carbon;
use Sambat\NepaliCalendar\Exceptions\NepaliDateOutOfRangeException;
use Sambat\NepaliCalendar\NepaliDate;

it('parses many input shapes as BS dates', function (mixed $input, string $expected) {
    expect(NepaliDate::parse($input)->toDateString())->toBe($expected);
})->with([
    ['2081-11-28', '2081-11-28'],
    ['2081/11/28', '2081-11-28'],
    ['2081.11.28', '2081-11-28'],
    ['2081 11 28', '2081-11-28'],
    ['20811128', '2081-11-28'],
    ['2081-1-5', '2081-01-05'],
    ['२०८१-११-२८', '2081-11-28'],
    ['2081 Falgun 28', '2081-11-28'],
    ['2081-Falgun-28', '2081-11-28'],
    ['2081-फागुन-28', '2081-11-28'],
    ['2081 फागुन 28', '2081-11-28'],
    [['year' => 2081, 'month' => 11, 'day' => 28], '2081-11-28'],
    [[2081, 11, 28], '2081-11-28'],
    [Carbon::create(2025, 3, 12), '2081-11-28'],
]);

it('parses with a time component', function () {
    $date = NepaliDate::parse('2081-11-28 14:30:45');

    expect($date->toDateString())->toBe('2081-11-28');
    expect($date->ad()->format('H:i:s'))->toBe('14:30:45');
});

it('builds from AD values', function () {
    expect(NepaliDate::fromAd('2025-02-17')->toDateString())->toBe('2081-11-05');
    expect(NepaliDate::fromAd(['year' => 2025, 'month' => 2, 'day' => 17])->toDateString())->toBe('2081-11-05');
    expect(NepaliDate::fromAd([2025, 2, 17])->toDateString())->toBe('2081-11-05');
});

it('exposes components and derived values', function () {
    $date = NepaliDate::parse('2081-11-28');

    expect($date->year())->toBe(2081);
    expect($date->month())->toBe(11);
    expect($date->day())->toBe(28);
    expect($date->quarter())->toBe(4);
    expect($date->dayOfYear())->toBe(334); // 306 days before Falgun + 28
    expect($date->weekOfYear())->toBe(48);
    expect($date->daysInMonth())->toBe(29);
    expect($date->daysInYear())->toBe(366);
    expect($date->isLeapYear())->toBeTrue();
    expect($date->ad()->format('Y-m-d'))->toBe('2025-03-12');
    expect($date->year)->toBe(2081);
    expect($date->month)->toBe(11);
    expect($date->day)->toBe(28);
});

it('does day arithmetic across month and year boundaries', function () {
    expect(NepaliDate::parse('2081-11-05')->addDays(1)->toDateString())->toBe('2081-11-06');
    expect(NepaliDate::parse('2081-11-05')->subDays(1)->toDateString())->toBe('2081-11-04');
    expect(NepaliDate::parse('2081-12-31')->addDays(1)->toDateString())->toBe('2082-01-01');
    expect(NepaliDate::parse('2081-09-29')->addDays(1)->toDateString())->toBe('2081-10-01');
    expect(NepaliDate::parse('2081-11-05')->addWeeks(2)->toDateString())->toBe('2081-11-19');
    expect(NepaliDate::parse('2081-11-05')->nextDay()->toDateString())->toBe('2081-11-06');
    expect(NepaliDate::parse('2081-11-05')->previousDay()->toDateString())->toBe('2081-11-04');
});

it('clamps month arithmetic to the target month length', function () {
    // Magh 30 (last day) + 1 month => Falgun 29 (last day of a 29-day month).
    expect(NepaliDate::parse('2081-10-30')->addMonths(1)->toDateString())->toBe('2081-11-29');
    expect(NepaliDate::parse('2081-12-15')->addMonths(1)->toDateString())->toBe('2082-01-15');
    expect(NepaliDate::parse('2081-01-15')->subMonths(1)->toDateString())->toBe('2080-12-15');
    expect(NepaliDate::parse('2081-11-05')->addYears(1)->toDateString())->toBe('2082-11-05');
    expect(NepaliDate::parse('2081-01-31')->addMonths(10)->toDateString())->toBe('2081-11-29');
    expect(NepaliDate::parse('2081-11-05')->nextMonth()->toDateString())->toBe('2081-12-05');
    expect(NepaliDate::parse('2081-11-05')->nextYear()->toDateString())->toBe('2082-11-05');
});

it('throws when arithmetic leaves the supported range', function () {
    expect(fn () => NepaliDate::parse('2099-12-30')->addDays(1))->toThrow(NepaliDateOutOfRangeException::class);
    expect(fn () => NepaliDate::parse('2000-01-01')->subDays(1))->toThrow(NepaliDateOutOfRangeException::class);
});

it('computes start and end of periods', function () {
    $date = NepaliDate::parse('2081-11-15');

    expect($date->startOfMonth()->toDateString())->toBe('2081-11-01');
    expect($date->endOfMonth()->toDateString())->toBe('2081-11-29');
    expect($date->startOfYear()->toDateString())->toBe('2081-01-01');
    expect($date->endOfYear()->toDateString())->toBe('2081-12-31');
    expect($date->startOfQuarter()->toDateString())->toBe('2081-10-01');
    expect($date->endOfQuarter()->toDateString())->toBe('2081-12-31');
    expect($date->firstOfMonth()->toDateString())->toBe('2081-11-01');
    expect($date->lastOfMonth()->toDateString())->toBe('2081-11-29');
});

it('computes weeks starting on Sunday and Monday', function () {
    // 2081-11-05 = Monday 2025-02-17.
    expect(NepaliDate::parse('2081-11-05')->startOfWeek()->toDateString())->toBe('2081-11-04');
    expect(NepaliDate::parse('2081-11-05')->endOfWeek()->toDateString())->toBe('2081-11-10');
    expect(NepaliDate::parse('2081-11-05')->startOfWeek('monday')->toDateString())->toBe('2081-11-05');
    expect(NepaliDate::parse('2081-11-05')->endOfWeek('monday')->toDateString())->toBe('2081-11-11');
});

it('computes differences', function () {
    $a = NepaliDate::parse('2081-11-05');
    $b = NepaliDate::parse('2081-11-28');

    expect($a->diffInDays($b))->toBe(23);
    expect($a->diffInDays($b, absolute: false))->toBe(23);
    expect($b->diffInDays($a, absolute: false))->toBe(-23);
    expect($a->diffInWeeks($b))->toBe(3);
    expect(NepaliDate::parse('2081-01-15')->diffInMonths(NepaliDate::parse('2082-01-15')))->toBe(12);
    expect(NepaliDate::parse('2081-01-15')->diffInMonths(NepaliDate::parse('2081-02-14')))->toBe(0);
    expect(NepaliDate::parse('2081-01-15')->diffInMonths(NepaliDate::parse('2081-02-15')))->toBe(1);
    expect(NepaliDate::parse('2080-01-01')->diffInYears(NepaliDate::parse('2082-01-01')))->toBe(2);
    expect(NepaliDate::parse('2080-06-01')->diffInYears(NepaliDate::parse('2082-01-01')))->toBe(1);
    expect(NepaliDate::parse('2080-01-01')->diffInYears(NepaliDate::parse('2081-11-05')))->toBe(1);
});

it('describes differences in Nepali and English', function () {
    $a = NepaliDate::parse('2081-11-05');
    $b = NepaliDate::parse('2081-11-06');

    expect($a->diffForHumans($b))->toBe('हिजो');
    expect($b->diffForHumans($a))->toBe('भोलि');
    expect($a->diffForHumans($b, 'english'))->toBe('yesterday');
    expect($b->diffForHumans($a, 'english'))->toBe('tomorrow');
    expect(NepaliDate::parse('2081-11-05')->diffForHumans(NepaliDate::parse('2081-11-07')))->toBe('२ दिन अघि');
    expect(NepaliDate::parse('2081-11-05')->diffForHumans(NepaliDate::parse('2081-11-07'), 'english'))->toBe('2 days ago');
    expect(NepaliDate::parse('2080-01-01')->diffForHumans(NepaliDate::parse('2081-11-05'), 'english'))->toBe('1 year ago');
});

it('supports comparisons', function () {
    $a = NepaliDate::parse('2081-11-05');
    $b = NepaliDate::parse('2081-11-28');

    expect($a->equals($b))->toBeFalse();
    expect($a->equals('2081-11-05'))->toBeTrue();
    expect($a->isSameDay(NepaliDate::parse('2081-11-05')))->toBeTrue();
    expect($a->isSameMonth($b))->toBeTrue();
    expect($a->isSameYear($b))->toBeTrue();
    expect($a->isBefore($b))->toBeTrue();
    expect($b->isAfter($a))->toBeTrue();
    expect($a->isBetween(NepaliDate::parse('2081-11-01'), $b))->toBeTrue();
    expect($b->isBetween($a, NepaliDate::parse('2081-11-27')))->toBeFalse();
    expect(NepaliDate::min($b, $a)->toDateString())->toBe('2081-11-05');
    expect(NepaliDate::max($b, $a)->toDateString())->toBe('2081-11-28');
    expect(NepaliDate::compare($a, $b))->toBe(-1);
});

it('detects weekends and special days', function () {
    expect(NepaliDate::parse('2081-01-01')->isSaturday())->toBeTrue();
    expect(NepaliDate::parse('2081-01-01')->isWeekend())->toBeTrue();
    expect(NepaliDate::parse('2081-11-05')->isSaturday())->toBeFalse();
    expect(NepaliDate::parse('2081-11-05')->isSunday())->toBeFalse();
});

it('builds month calendar grids', function () {
    // Falgun 2081 = 29 days, starting Thursday 2025-02-13.
    $grid = NepaliDate::parse('2081-11-15')->calendar();

    expect(count($grid))->toBe(5);
    expect($grid[0][0])->toBeNull();
    expect($grid[0][3])->toBeNull();
    expect($grid[0][4]?->day())->toBe(1); // Thursday lands on the 5th column (Sun-first)
    expect($grid[4][4]?->day())->toBe(29);
    expect($grid[4][5])->toBeNull();
    expect($grid[4][6])->toBeNull();
});

it('is immutable', function () {
    $date = NepaliDate::parse('2081-11-05');
    $copy = $date->copy();

    expect($date->addDays(1)->toDateString())->toBe('2081-11-06');
    expect($date->toDateString())->toBe('2081-11-05');
    expect($date->equals($copy))->toBeTrue();
});

it('supports with* modifiers', function () {
    expect(NepaliDate::parse('2081-11-05')->withYear(2085)->toDateString())->toBe('2085-11-05');
    expect(NepaliDate::parse('2081-01-31')->withMonth(11)->toDateString())->toBe('2081-11-29');
    expect(NepaliDate::parse('2081-11-05')->withDay(20)->toDateString())->toBe('2081-11-20');
});

it('serializes to array, JSON and back', function () {
    $date = NepaliDate::parse('2081-11-05');

    $array = $date->toArray();
    expect($array['year'])->toBe(2081);
    expect($array['month'])->toBe(11);
    expect($array['day'])->toBe(5);
    expect($array['num_week_day'])->toBe(2);
    expect($array['week_day'])->toBe('सोमबार');
    expect($array['month_name'])->toBe('फागुन');
    expect($array['ad_date'])->toBe('2025-02-17');

    expect(json_decode($date->toJson(), true))->toBe($array);
    expect(json_decode(json_encode($date), true))->toBe($array);

    $restored = unserialize(serialize($date));
    expect($restored)->toBeInstanceOf(NepaliDate::class);
    expect($restored->toDateString())->toBe('2081-11-05');
});

it('uses default format for string conversion', function () {
    expect((string) NepaliDate::parse('2081-11-05'))->toBe('२०८१-११-०५');
});
