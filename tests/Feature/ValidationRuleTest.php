<?php

use Illuminate\Support\Facades\Validator;
use Sambat\NepaliCalendar\Rules\NepaliDateAfterRule;
use Sambat\NepaliCalendar\Rules\NepaliDateBeforeRule;
use Sambat\NepaliCalendar\Rules\NepaliDateBetweenRule;

it('validates nepali_date_before', function () {
    $pass = Validator::make(['date' => '2081-11-05'], ['date' => 'nepali_date_before:2081-11-29']);
    expect($pass->passes())->toBeTrue();

    $fail = Validator::make(['date' => '2081-12-05'], ['date' => 'nepali_date_before:2081-11-29']);
    expect($fail->passes())->toBeFalse();
    expect($fail->errors()->first('date'))->toBe('The date must be a date before 2081-11-29.');

    $invalid = Validator::make(['date' => '2081-13-99'], ['date' => 'nepali_date_before:2081-11-29']);
    expect($invalid->passes())->toBeFalse();
});

it('validates nepali_date_after', function () {
    $pass = Validator::make(['date' => '2081-12-05'], ['date' => 'nepali_date_after:2081-11-29']);
    expect($pass->passes())->toBeTrue();

    $fail = Validator::make(['date' => '2081-11-05'], ['date' => 'nepali_date_after:2081-11-29']);
    expect($fail->passes())->toBeFalse();
    expect($fail->errors()->first('date'))->toBe('The date must be a date after 2081-11-29.');
});

it('validates nepali_date_between', function () {
    $pass = Validator::make(['date' => '2081-11-15'], ['date' => 'nepali_date_between:2081-11-01,2081-11-29']);
    expect($pass->passes())->toBeTrue();

    // Inclusive bounds.
    expect(Validator::make(['date' => '2081-11-01'], ['date' => 'nepali_date_between:2081-11-01,2081-11-29'])->passes())->toBeTrue();
    expect(Validator::make(['date' => '2081-11-29'], ['date' => 'nepali_date_between:2081-11-01,2081-11-29'])->passes())->toBeTrue();

    $fail = Validator::make(['date' => '2081-12-01'], ['date' => 'nepali_date_between:2081-11-01,2081-11-29']);
    expect($fail->passes())->toBeFalse();
    expect($fail->errors()->first('date'))->toBe('The date must be a date between 2081-11-01 and 2081-11-29.');
});

it('supports the object-style rule classes', function () {
    $pass = Validator::make(['date' => '2081-11-05'], ['date' => new NepaliDateBeforeRule('2081-11-29')]);
    expect($pass->passes())->toBeTrue();

    expect(Validator::make(['date' => '2081-12-05'], ['date' => new NepaliDateAfterRule('2081-11-29')])->passes())->toBeTrue();
    expect(Validator::make(['date' => '2081-11-15'], ['date' => new NepaliDateBetweenRule('2081-11-01', '2081-11-29')])->passes())->toBeTrue();

    expect(Validator::make(['date' => '2081-12-05'], ['date' => new NepaliDateBeforeRule('2081-11-29')])->passes())->toBeFalse();
    expect(Validator::make(['date' => '2081-11-05'], ['date' => new NepaliDateAfterRule('2081-11-29')])->passes())->toBeFalse();
    expect(Validator::make(['date' => '2081-12-01'], ['date' => new NepaliDateBetweenRule('2081-11-01', '2081-11-29')])->passes())->toBeFalse();
});
