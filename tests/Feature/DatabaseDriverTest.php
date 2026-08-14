<?php

use Illuminate\Support\Facades\DB;
use Sambat\NepaliCalendar\Calendar;
use Sambat\NepaliCalendar\Constants\CalendarData;
use Sambat\NepaliCalendar\Contracts\CalendarDataProvider;
use Sambat\NepaliCalendar\NepaliDate;
use Sambat\NepaliCalendar\Providers\ArrayCalendarDataProvider;
use Sambat\NepaliCalendar\Providers\DatabaseCalendarDataProvider;

beforeEach(function () {
    $this->artisan('migrate', [
        '--path' => __DIR__.'/../../database/migrations',
        '--realpath' => true,
    ]);

    $this->artisan('nepali:seed');

    config(['nepali-calendar.driver' => 'database']);
    Calendar::resetProvider();
});

afterEach(function () {
    Calendar::resetProvider();
    config(['nepali-calendar.driver' => 'algorithm']);
});

it('resolves the database provider from the config driver', function () {
    expect(Calendar::provider())->toBeInstanceOf(DatabaseCalendarDataProvider::class);
    expect(Calendar::supportedYears())->toBe(['min' => 2000, 'max' => 2100]);
});

it('converts dates from the database exactly like the built-in data', function () {
    $samples = [
        ['2025-02-17', '2081-11-05'],
        ['2024-04-13', '2081-01-01'],
        ['1943-04-14', '2000-01-01'],
        ['2043-04-13', '2099-12-30'],
    ];

    foreach ($samples as [$ad, $bs]) {
        expect(NepaliDate::fromAd($ad)->toDateString())->toBe($bs);
        expect(NepaliDate::parse($bs)->formatAd('Y-m-d'))->toBe($ad);
    }

    // The two drivers must agree on every BS year's day count.
    $fromDb = Calendar::provider()->allMonthLengths();

    Calendar::setProvider(new ArrayCalendarDataProvider);

    expect(Calendar::provider()->allMonthLengths())->toBe($fromDb);
});

it('seeds through the artisan command and guards against double seeding', function () {
    $this->artisan('nepali:seed')->assertFailed();

    $this->artisan('nepali:seed', ['--fresh' => true])
        ->expectsOutputToContain('Seeded 101 BS years')
        ->assertSuccessful();

    expect(DB::table('nepali_calendar_years')->count())->toBe(101);
});

it('fails with a helpful message when the table is empty', function () {
    DB::table('nepali_calendar_years')->delete();
    Calendar::resetProvider();

    Calendar::provider()->allMonthLengths();
})->throws(RuntimeException::class, 'nepali:seed');

it('rejects a table that does not start at the anchor year', function () {
    DB::table('nepali_calendar_years')->delete();
    DB::table('nepali_calendar_years')->insert([
        'bs_year' => 2080,
        'months' => json_encode(CalendarData::NEPALI_YEARS[2080]),
    ]);
    Calendar::resetProvider();

    Calendar::provider()->allMonthLengths();
})->throws(RuntimeException::class, 'contiguous range');

it('uses a custom provider bound in the container', function () {
    app()->instance('nepali-calendar.provider', new class implements CalendarDataProvider
    {
        public function allMonthLengths(): array
        {
            return [2000 => array_fill(0, 12, 30)];
        }

        public function minYear(): int
        {
            return 2000;
        }

        public function maxYear(): int
        {
            return 2000;
        }
    });

    Calendar::resetProvider();

    expect(Calendar::supportedYears())->toBe(['min' => 2000, 'max' => 2000]);
    expect(Calendar::daysInBsMonth(2000, 1))->toBe(30);
    expect(NepaliDate::fromBs(2000, 1, 1)->formatAd('Y-m-d'))->toBe('1943-04-14');
});

it('works in plain PHP with a raw PDO connection', function () {
    $pdo = new PDO('sqlite::memory:');
    $pdo->exec('CREATE TABLE nepali_calendar_years (bs_year INTEGER PRIMARY KEY, months TEXT)');

    $stmt = $pdo->prepare('INSERT INTO nepali_calendar_years (bs_year, months) VALUES (?, ?)');

    foreach (CalendarData::NEPALI_YEARS as $year => $months) {
        $stmt->execute([$year, json_encode($months)]);
    }

    Calendar::setProvider(new DatabaseCalendarDataProvider($pdo));

    expect(Calendar::daysInBsMonth(2081, 1))->toBe(31);
    expect(NepaliDate::fromBs(2081, 1, 1)->formatAd('Y-m-d'))->toBe('2024-04-13');
    expect(NepaliDate::fromAd('2025-02-17')->toDateString())->toBe('2081-11-05');
    expect(NepaliDate::parse('2081-11-05')->formatAd('Y-m-d'))->toBe('2025-02-17');
});
