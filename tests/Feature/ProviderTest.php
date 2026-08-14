<?php

use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Sambat\NepaliCalendar\Facades\NepaliDate;
use Sambat\NepaliCalendar\NepaliDate as NepaliDateObject;
use Sambat\NepaliCalendar\NepaliDateFactory;
use Sambat\NepaliCalendar\Rules\NepaliDateFormatRule;
use Sambat\NepaliCalendar\Rules\NepaliDateRule;

it('registers config and the facade binding', function () {
    expect(config('nepali-calendar.language'))->toBe('nepali');
    expect(config('nepali-calendar.numerals'))->toBe('devanagari');
    expect(app('nepali-date'))->toBeInstanceOf(NepaliDateFactory::class);
    expect(NepaliDate::now())->toBeInstanceOf(NepaliDateObject::class);
    expect(NepaliDate::fromAd('2025-02-17')->toDateString())->toBe('2081-11-05');
    expect(NepaliDate::daysInMonth(2081, 11))->toBe(29);
    expect(NepaliDate::isLeapYear(2081))->toBeTrue();
    expect(NepaliDate::isValid('2081-11-28'))->toBeTrue();
    expect(NepaliDate::isValid('2081-13-01'))->toBeFalse();
});

it('exposes month and weekday name lists', function () {
    expect(NepaliDate::monthNames()[11])->toBe('फागुन');
    expect(NepaliDate::monthNames('roman')[11])->toBe('Falgun');
    expect(NepaliDate::monthNames('english')[11])->toBe('November');
    expect(NepaliDate::weekDayNames()[2])->toBe('सोमबार');
    expect(NepaliDate::range()['bs'])->toBe(['min' => '2000-01-01', 'max' => '2100-12-30']);
});

it('supports the nepali_date validation rule', function () {
    $validator = Validator::make(
        ['date' => '2081-11-28'],
        ['date' => ['required', 'nepali_date']]
    );

    expect($validator->passes())->toBeTrue();

    $validator = Validator::make(
        ['date' => '2081-13-01'],
        ['date' => ['required', 'nepali_date']]
    );

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->first('date'))->toContain('valid Nepali');
});

it('supports the nepali_date_format validation rule', function () {
    $validator = Validator::make(
        ['date' => '2081-11-28'],
        ['date' => ['required', 'nepali_date_format:Y-m-d']]
    );

    expect($validator->passes())->toBeTrue();

    $validator = Validator::make(
        ['date' => '2081-11-5'],
        ['date' => ['required', 'nepali_date_format:Y-m-d']]
    );

    expect($validator->fails())->toBeTrue();
});

it('works with the rule classes directly', function () {
    $validator = Validator::make(['date' => '2081-11-28'], ['date' => [new NepaliDateRule]]);
    expect($validator->passes())->toBeTrue();

    $validator = Validator::make(['date' => '2081-11-28'], ['date' => [new NepaliDateFormatRule('Y-m-d')]]);
    expect($validator->passes())->toBeTrue();
});

it('registers Blade directives', function () {
    // Strings are parsed BS-first by design.
    $compiled = app('blade.compiler')->compileString("@nepaliDate('2025-02-17')");

    expect($compiled)->toContain('Blade::render');

    ob_start();
    eval('?>'.$compiled);
    $output = ob_get_clean();

    expect($output)->toBe('२०२५-०२-१७');

    // AD values convert automatically when passed as Carbon / NepaliDate.
    $compiled = app('blade.compiler')->compileString(
        "@nepaliDate(\Sambat\NepaliCalendar\NepaliDate::fromAd('2025-02-17'))"
    );

    ob_start();
    eval('?>'.$compiled);

    expect(ob_get_clean())->toBe('२०८१-११-०५');
});

it('registers Carbon macros', function () {
    $carbon = Carbon::parse('2025-02-17');

    expect($carbon->toNepaliDate()->toDateString())->toBe('2081-11-05');
    expect($carbon->formatNepali('Y-m-d'))->toBe('२०८१-११-०५');
});

it('provides the global helper functions', function () {
    expect(ad_to_bs('2025-02-17'))->toBe('२०८१-११-०५');
    expect(bs_to_ad('2081-11-05'))->toBe('2025-02-17');
    expect(nepali_number('2081'))->toBe('२०८१');
    expect(english_number('२०८१'))->toBe('2081');
    expect(bs_days_in_month(2081, 11))->toBe(29);
    expect(bs_days_in_year(2081))->toBe(366);
    expect(bs_is_leap_year(2081))->toBeTrue();
    expect(nepali_date('2081-11-05'))->toBeInstanceOf(NepaliDateObject::class);
    expect(bs_today())->toMatch('/[०-९]{4}-[०-९]{2}-[०-९]{2}/u');
    expect(bs_age('2000-01-01'))->toBeInt();
    expect(bs_diff_for_humans('2081-11-05', '2081-11-06'))->toBe('हिजो');
});

it('exposes the nepali:convert artisan command', function () {
    $this->artisan('nepali:convert', ['date' => '2025-02-17'])
        ->expectsOutput('२०८१-११-०५')
        ->assertSuccessful();

    $this->artisan('nepali:convert', ['date' => '2081-11-05', '--from' => 'bs', '--to' => 'ad', '--format' => 'Y-m-d'])
        ->expectsOutput('2025-02-17')
        ->assertSuccessful();

    $this->artisan('nepali:convert', ['date' => 'not-a-date'])
        ->assertFailed();
});

it('exposes the nepali:info artisan command', function () {
    $this->artisan('nepali:info', ['date' => '2081-11-05', '--language' => 'roman'])
        ->expectsOutputToContain('Falgun')
        ->assertSuccessful();
});

it('round-trips a full month through the facade', function () {
    $start = NepaliDate::fromBs(2081, 11, 1);
    $end = NepaliDate::fromBs(2081, 11, 29);

    expect($start->diffInDays($end))->toBe(28);
    expect(NepaliDate::parse($start->toArray())->toDateString())->toBe('2081-11-01');
});
