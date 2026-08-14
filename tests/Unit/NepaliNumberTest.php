<?php

use Sambat\NepaliCalendar\NepaliNumber;

it('converts numerals in both directions', function () {
    expect(NepaliNumber::toNepali(2083))->toBe('२०८३')
        ->and(NepaliNumber::toNepali('2026.50'))->toBe('२०२६.५०')
        ->and(NepaliNumber::toEnglish('२०८३'))->toBe('2083')
        ->and(NepaliNumber::toEnglish('१२,५०,०००'))->toBe('12,50,000');
});

it('detects Devanagari numerals', function () {
    expect(NepaliNumber::containsNepali('२०८१'))->toBeTrue()
        ->and(NepaliNumber::containsNepali('2081'))->toBeFalse()
        ->and(NepaliNumber::containsNepali('१०० रुपैयाँ'))->toBeTrue();
});

it('formats with Indian grouping', function () {
    expect(NepaliNumber::format(999))->toBe('९९९')
        ->and(NepaliNumber::format(1000))->toBe('१,०००')
        ->and(NepaliNumber::format(125000))->toBe('१,२५,०००')
        ->and(NepaliNumber::format(1250000))->toBe('१२,५०,०००')
        ->and(NepaliNumber::format(1234567890))->toBe('१,२३,४५,६७,८९०')
        ->and(NepaliNumber::format(125000, false))->toBe('1,25,000')
        ->and(NepaliNumber::format(-125000))->toBe('-१,२५,०००');
});

it('spells numbers out in English with Indian units', function () {
    expect(NepaliNumber::toEnglishWords(0))->toBe('zero')
        ->and(NepaliNumber::toEnglishWords(25))->toBe('twenty-five')
        ->and(NepaliNumber::toEnglishWords(100))->toBe('one hundred')
        ->and(NepaliNumber::toEnglishWords(999))->toBe('nine hundred and ninety-nine')
        ->and(NepaliNumber::toEnglishWords(1000))->toBe('one thousand')
        ->and(NepaliNumber::toEnglishWords(1100))->toBe('one thousand one hundred')
        ->and(NepaliNumber::toEnglishWords(125000))->toBe('one lakh twenty-five thousand')
        ->and(NepaliNumber::toEnglishWords(999999))->toBe('nine lakh ninety-nine thousand nine hundred and ninety-nine')
        ->and(NepaliNumber::toEnglishWords(10000000))->toBe('one crore')
        ->and(NepaliNumber::toEnglishWords(123456789))->toBe(
            'twelve crore thirty-four lakh fifty-six thousand seven hundred and eighty-nine'
        );
});

it('spells numbers out in Nepali', function () {
    expect(NepaliNumber::toNepaliWords(0))->toBe('शून्य')
        ->and(NepaliNumber::toNepaliWords(5))->toBe('पाँच')
        ->and(NepaliNumber::toNepaliWords(12))->toBe('बाह्र')
        ->and(NepaliNumber::toNepaliWords(25))->toBe('पच्चीस')
        ->and(NepaliNumber::toNepaliWords(100))->toBe('एक सय')
        ->and(NepaliNumber::toNepaliWords(999))->toBe('नौ सय उनान्सय')
        ->and(NepaliNumber::toNepaliWords(1000))->toBe('एक हजार')
        ->and(NepaliNumber::toNepaliWords(1100))->toBe('एक हजार एक सय')
        ->and(NepaliNumber::toNepaliWords(125000))->toBe('एक लाख पच्चीस हजार')
        ->and(NepaliNumber::toNepaliWords(999999))->toBe('नौ लाख उनान्सय हजार नौ सय उनान्सय')
        ->and(NepaliNumber::toNepaliWords(10000000))->toBe('एक करोड')
        ->and(NepaliNumber::toNepaliWords(123456789))->toBe(
            'बाह्र करोड चौँतीस लाख छपन्न हजार सात सय उनान्नब्बे'
        );
});

it('resolves the words language argument', function () {
    expect(NepaliNumber::toWords(25))->toBe('पच्चीस')
        ->and(NepaliNumber::toWords(25, 'nepali'))->toBe('पच्चीस')
        ->and(NepaliNumber::toWords(25, 'english'))->toBe('twenty-five')
        ->and(NepaliNumber::toWords(25, 'ENGLISH'))->toBe('twenty-five');
});

it('handles negatives in words', function () {
    expect(NepaliNumber::toEnglishWords(-25))->toBe('minus twenty-five')
        ->and(NepaliNumber::toNepaliWords(-25))->toBe('ऋण पच्चीस');
});

it('rejects numbers beyond the word range', function () {
    NepaliNumber::toNepaliWords(10000000000);
})->throws(InvalidArgumentException::class, 'out of range');

it('provides the nepali_number_words helper', function () {
    expect(nepali_number_words(125000))->toBe('एक लाख पच्चीस हजार')
        ->and(nepali_number_words(125000, 'english'))->toBe('one lakh twenty-five thousand');
});
