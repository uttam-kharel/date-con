<?php

declare(strict_types=1);

namespace Sambat\NepaliCalendar;

use InvalidArgumentException;
use Sambat\NepaliCalendar\Support\NumberConverter;

/**
 * Public numeral engine for Nepali (Devanagari) numbers.
 *
 * Converts between western and Devanagari digits, formats amounts with the
 * Indian (lakh/crore) grouping and spells numbers out in English and Nepali —
 * handy for invoices, receipts, cheques and financial reports:
 *
 *     NepaliNumber::toNepali(125000);                    // १,२५,०००
 *     NepaliNumber::format(125000);                      // १,२५,०००
 *     NepaliNumber::toEnglishWords(125000);              // one lakh twenty-five thousand
 *     NepaliNumber::toNepaliWords(125000);               // एक लाख पच्चीस हजार
 *
 * Words support the full Indian numbering system up to 99 crore
 * (0 – 99,99,99,999); larger values throw an InvalidArgumentException.
 */
final class NepaliNumber
{
    /** Maximum supported value for number-to-words: 99,99,99,999. */
    public const MAX_WORDS = 9999999999;

    private const ENGLISH_DIGITS = [
        'zero', 'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine',
    ];

    private const ENGLISH_TEENS = [
        10 => 'ten', 'eleven', 'twelve', 'thirteen', 'fourteen', 'fifteen', 'sixteen',
        'seventeen', 'eighteen', 'nineteen',
    ];

    private const ENGLISH_TENS = [
        20 => 'twenty', 30 => 'thirty', 40 => 'forty', 50 => 'fifty',
        60 => 'sixty', 70 => 'seventy', 80 => 'eighty', 90 => 'ninety',
    ];

    private const NEPALI_DIGITS = [
        'शून्य', 'एक', 'दुई', 'तीन', 'चार', 'पाँच', 'छ', 'सात', 'आठ', 'नौ',
    ];

    private const NEPALI_TEENS = [
        10 => 'दश', 'एघार', 'बाह्र', 'तेह्र', 'चौध', 'पन्ध्र', 'सोह्र', 'सत्र', 'अठार', 'उन्नाइस',
    ];

    private const NEPALI_TENS = [
        20 => 'बीस', 30 => 'तीस', 40 => 'चालीस', 50 => 'पचास',
        60 => 'साठी', 70 => 'सत्तरी', 80 => 'असी', 90 => 'नब्बे',
    ];

    /** The irregular two-digit Nepali words (21–99, skipping the tens). */
    private const NEPALI_COMPOUNDS = [
        21 => 'एक्काइस', 22 => 'बाइस', 23 => 'तेइस', 24 => 'चौबिस', 25 => 'पच्चीस',
        26 => 'छब्बिस', 27 => 'सत्ताइस', 28 => 'अट्ठाइस', 29 => 'उनन्तीस',
        31 => 'एकतीस', 32 => 'बत्तीस', 33 => 'तेत्तीस', 34 => 'चौँतीस', 35 => 'पैँतीस',
        36 => 'छत्तीस', 37 => 'सैँतीस', 38 => 'अठतीस', 39 => 'उनन्चालीस',
        41 => 'एकचालीस', 42 => 'बयालीस', 43 => 'त्रिचालीस', 44 => 'चवालीस', 45 => 'पैँतालीस',
        46 => 'छयालीस', 47 => 'सतचालीस', 48 => 'अठचालीस', 49 => 'उनन्चास',
        51 => 'एकाउन्न', 52 => 'बाउन्न', 53 => 'त्रिपन्न', 54 => 'चवन्न', 55 => 'पचपन्न',
        56 => 'छपन्न', 57 => 'सन्ताउन्न', 58 => 'अन्ठाउन्न', 59 => 'उनन्साठी',
        61 => 'एकसट्ठी', 62 => 'बयसट्ठी', 63 => 'त्रिसट्ठी', 64 => 'चौसट्ठी', 65 => 'पैँसट्ठी',
        66 => 'छैसट्ठी', 67 => 'सतसट्ठी', 68 => 'अठसट्ठी', 69 => 'उनन्सत्तरी',
        71 => 'एकहत्तर', 72 => 'बहत्तर', 73 => 'त्रिहत्तर', 74 => 'चौहत्तर', 75 => 'पचहत्तर',
        76 => 'छयहत्तर', 77 => 'सतहत्तर', 78 => 'अठहत्तर', 79 => 'उनासी',
        81 => 'एकासी', 82 => 'बयासी', 83 => 'त्रियासी', 84 => 'चौरासी', 85 => 'पचासी',
        86 => 'छयासी', 87 => 'सतासी', 88 => 'अठासी', 89 => 'उनान्नब्बे',
        91 => 'एकान्नब्बे', 92 => 'बयान्नब्बे', 93 => 'त्रियान्नब्बे', 94 => 'चौरान्नब्बे',
        95 => 'पन्चान्नब्बे', 96 => 'छयान्नब्बे', 97 => 'सन्तान्नब्बे', 98 => 'अन्ठान्नब्बे',
        99 => 'उनान्सय',
    ];

    /** Western → Devanagari digits: 2083 → २०८३. */
    public static function toNepali(string|int|float $value): string
    {
        return NumberConverter::toNepali($value);
    }

    /** Devanagari → western digits: २०८३ → 2083. */
    public static function toEnglish(string|int|float $value): string
    {
        return NumberConverter::toEnglish($value);
    }

    /** True when the value contains Devanagari numerals. */
    public static function containsNepali(string $value): bool
    {
        return NumberConverter::containsNepaliNumerals($value);
    }

    /**
     * Format an integer with Indian (lakh/crore) grouping: 1250000 → "१२,५०,०००".
     * Pass $devanagari = false for western digits.
     */
    public static function format(int|string $value, bool $devanagari = true, string $separator = ','): string
    {
        $number = (string) (int) $value;
        $negative = str_starts_with($number, '-');

        if ($negative) {
            $number = substr($number, 1);
        }

        $lastThree = substr($number, -3);
        $rest = substr($number, 0, -3);

        if ($rest !== '') {
            $groups = [];
            while (strlen($rest) > 2) {
                $groups[] = substr($rest, -2);
                $rest = substr($rest, 0, -2);
            }
            $groups[] = $rest;

            $formatted = implode($separator, array_reverse($groups)).$separator.$lastThree;
        } else {
            $formatted = $lastThree;
        }

        if ($negative) {
            $formatted = '-'.$formatted;
        }

        return $devanagari ? self::toNepali($formatted) : $formatted;
    }

    /** Spell a number out in English using the Indian numbering system. */
    public static function toEnglishWords(int|string $value): string
    {
        return self::words((int) $value, 'english');
    }

    /** Spell a number out in Nepali (एक लाख पच्चीस हजार). */
    public static function toNepaliWords(int|string $value): string
    {
        return self::words((int) $value, 'nepali');
    }

    /** Spell a number out in the requested language: 'nepali' (default) | 'english'. */
    public static function toWords(int|string $value, string $language = 'nepali'): string
    {
        return self::words((int) $value, strtolower($language) === 'english' ? 'english' : 'nepali');
    }

    /**
     * Format a money amount with Indian grouping and two decimals:
     *
     *     NepaliNumber::formatCurrency(125000.5);   // रु. १,२५,०००.५०
     *     NepaliNumber::formatCurrency(125000.5, 'english'); // Rs. 1,25,000.50
     */
    public static function formatCurrency(string|int|float $amount, string $language = 'nepali'): string
    {
        $english = strtolower($language) === 'english';
        $negative = (float) $amount < 0;

        [$int, $dec] = array_pad(explode('.', number_format(abs((float) $amount), 2, '.', '')), 2, '00');

        $formatted = $english
            ? 'Rs. '.self::format((int) $int, false).'.'.$dec
            : 'रु. '.self::format((int) $int, true).'.'.self::toNepali($dec);

        if ($negative) {
            $formatted = $english ? '-'.$formatted : 'ऋण '.$formatted;
        }

        return $formatted;
    }

    /**
     * Spell a money amount out in words (cheque / receipt style):
     *
     *     NepaliNumber::currencyInWords(125000.5);   // रुपैयाँ एक लाख पच्चीस हजार पचास पैसा मात्र
     *     NepaliNumber::currencyInWords(125000.5, 'english'); // Rupees one lakh ... and fifty paise only
     */
    public static function currencyInWords(string|int|float $amount, string $language = 'nepali'): string
    {
        $english = strtolower($language) === 'english';

        [$int, $dec] = array_pad(explode('.', number_format(abs((float) $amount), 2, '.', '')), 2, '00');
        $intWords = self::words((int) $int, $english ? 'english' : 'nepali');
        $decWords = self::words((int) $dec, $english ? 'english' : 'nepali');

        if ($english) {
            $out = 'Rupees '.$intWords;
            if ((int) $dec > 0) {
                $out .= ' and '.$decWords.' paise';
            }

            return $out.' only';
        }

        $out = 'रुपैयाँ '.$intWords;
        if ((int) $dec > 0) {
            $out .= ' '.$decWords.' पैसा';
        }

        return $out.' मात्र';
    }

    private static function words(int $value, string $language): string
    {
        if (abs($value) > self::MAX_WORDS) {
            throw new InvalidArgumentException(
                'Number out of range for NepaliNumber words: '.$value.'. Maximum is 99,99,99,999.'
            );
        }

        $negative = $value < 0;

        if ($negative) {
            $value = -$value;
        }

        $text = match ($language) {
            'english' => self::englishWords($value),
            default => self::nepaliWords($value),
        };

        if ($negative) {
            $text = $language === 'english' ? 'minus '.$text : 'ऋण '.$text;
        }

        return $text;
    }

    private static function englishWords(int $value): string
    {
        if ($value === 0) {
            return 'zero';
        }

        $parts = [];

        if (($crore = intdiv($value, 10000000)) > 0) {
            $parts[] = self::englishUnderThousand($crore).' crore';
            $value %= 10000000;
        }

        if (($lakh = intdiv($value, 100000)) > 0) {
            $parts[] = self::englishUnderThousand($lakh).' lakh';
            $value %= 100000;
        }

        if (($thousand = intdiv($value, 1000)) > 0) {
            $parts[] = self::englishUnderThousand($thousand).' thousand';
            $value %= 1000;
        }

        if ($value > 0) {
            $parts[] = self::englishUnderThousand($value);
        }

        return implode(' ', $parts);
    }

    private static function englishUnderThousand(int $value): string
    {
        if ($value < 100) {
            return self::englishUnderHundred($value);
        }

        $hundreds = intdiv($value, 100);
        $rest = $value % 100;

        return $rest === 0
            ? self::ENGLISH_DIGITS[$hundreds].' hundred'
            : self::ENGLISH_DIGITS[$hundreds].' hundred and '.self::englishUnderHundred($rest);
    }

    private static function englishUnderHundred(int $value): string
    {
        if ($value < 10) {
            return self::ENGLISH_DIGITS[$value];
        }

        if ($value < 20) {
            return self::ENGLISH_TEENS[$value];
        }

        $tens = intdiv($value, 10) * 10;
        $ones = $value % 10;

        return $ones === 0
            ? self::ENGLISH_TENS[$tens]
            : self::ENGLISH_TENS[$tens].'-'.self::ENGLISH_DIGITS[$ones];
    }

    private static function nepaliWords(int $value): string
    {
        if ($value === 0) {
            return 'शून्य';
        }

        $parts = [];

        if (($crore = intdiv($value, 10000000)) > 0) {
            $parts[] = self::nepaliUnderThousand($crore).' करोड';
            $value %= 10000000;
        }

        if (($lakh = intdiv($value, 100000)) > 0) {
            $parts[] = self::nepaliUnderThousand($lakh).' लाख';
            $value %= 100000;
        }

        if (($thousand = intdiv($value, 1000)) > 0) {
            $parts[] = self::nepaliUnderThousand($thousand).' हजार';
            $value %= 1000;
        }

        if ($value > 0) {
            $parts[] = self::nepaliUnderThousand($value);
        }

        return implode(' ', $parts);
    }

    private static function nepaliUnderThousand(int $value): string
    {
        if ($value < 100) {
            return self::nepaliUnderHundred($value);
        }

        $hundreds = intdiv($value, 100);
        $rest = $value % 100;

        return $rest === 0
            ? self::NEPALI_DIGITS[$hundreds].' सय'
            : self::NEPALI_DIGITS[$hundreds].' सय '.self::nepaliUnderHundred($rest);
    }

    private static function nepaliUnderHundred(int $value): string
    {
        if ($value < 10) {
            return self::NEPALI_DIGITS[$value];
        }

        if (isset(self::NEPALI_TEENS[$value])) {
            return self::NEPALI_TEENS[$value];
        }

        if (isset(self::NEPALI_COMPOUNDS[$value])) {
            return self::NEPALI_COMPOUNDS[$value];
        }

        $tens = intdiv($value, 10) * 10;
        $ones = $value % 10;

        return $ones === 0
            ? self::NEPALI_TENS[$tens]
            : self::NEPALI_TENS[$tens].' '.self::NEPALI_DIGITS[$ones];
    }
}
