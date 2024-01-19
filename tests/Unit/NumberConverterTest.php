<?php

use Sambat\NepaliCalendar\Support\NumberConverter;

it('converts western digits to Devanagari', function () {
    expect(NumberConverter::toNepali('2081'))->toBe('२०८१');
    expect(NumberConverter::toNepali(2025))->toBe('२०२५');
    expect(NumberConverter::toNepali('2081-11-28'))->toBe('२०८१-११-२८');
});

it('converts Devanagari digits back to western', function () {
    expect(NumberConverter::toEnglish('२०८१'))->toBe('2081');
    expect(NumberConverter::toEnglish('०५/११/२०८१'))->toBe('05/11/2081');
});

it('detects Devanagari numerals', function () {
    expect(NumberConverter::containsNepaliNumerals('२०८१-११-२८'))->toBeTrue();
    expect(NumberConverter::containsNepaliNumerals('2081-11-28'))->toBeFalse();
});
