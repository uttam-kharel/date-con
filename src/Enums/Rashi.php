<?php

declare(strict_types=1);

namespace Sambat\NepaliCalendar\Enums;

use Sambat\NepaliCalendar\Exceptions\InvalidNepaliMonthException;

/**
 * The twelve rashis (zodiac signs) of the Nepali calendar, following the
 * common convention that maps each BS month to one sign:
 *
 *     Mesh (मेष)     Baisakh · Vrish (वृष)    Jestha · Mithun (मिथुन)  Ashadh
 *     Karkat (कर्कट) Shrawan · Simha (सिंह)  Bhadra · Kanya (कन्या)   Ashwin
 *     Tula (तुला)    Kartik  · Vrishchik (वृश्चिक) Mangsir · Dhanu (धनु) Poush
 *     Makar (मकर)    Magh    · Kumbha (कुम्भ) Falgun · Meena (मीन)   Chaitra
 */
enum Rashi: string
{
    case Mesh = 'mesh';
    case Vrish = 'vrish';
    case Mithun = 'mithun';
    case Karkat = 'karkat';
    case Simha = 'simha';
    case Kanya = 'kanya';
    case Tula = 'tula';
    case Vrishchik = 'vrishchik';
    case Dhanu = 'dhanu';
    case Makar = 'makar';
    case Kumbha = 'kumbha';
    case Meena = 'meena';

    /** Devanagari name (मेष, वृष, ...). */
    public function devanagari(): string
    {
        return match ($this) {
            self::Mesh => 'मेष',
            self::Vrish => 'वृष',
            self::Mithun => 'मिथुन',
            self::Karkat => 'कर्कट',
            self::Simha => 'सिंह',
            self::Kanya => 'कन्या',
            self::Tula => 'तुला',
            self::Vrishchik => 'वृश्चिक',
            self::Dhanu => 'धनु',
            self::Makar => 'मकर',
            self::Kumbha => 'कुम्भ',
            self::Meena => 'मीन',
        };
    }

    /** Latin transliteration (Mesh, Vrish, ...). */
    public function roman(): string
    {
        return ucfirst($this->value);
    }

    /** Western zodiac equivalent (Aries, Taurus, ...). */
    public function english(): string
    {
        return match ($this) {
            self::Mesh => 'Aries',
            self::Vrish => 'Taurus',
            self::Mithun => 'Gemini',
            self::Karkat => 'Cancer',
            self::Simha => 'Leo',
            self::Kanya => 'Virgo',
            self::Tula => 'Libra',
            self::Vrishchik => 'Scorpio',
            self::Dhanu => 'Sagittarius',
            self::Makar => 'Capricorn',
            self::Kumbha => 'Aquarius',
            self::Meena => 'Pisces',
        };
    }

    /** Name in the requested calendar language (default: config / Nepali). */
    public function name(?string $language = null): string
    {
        return match (CalendarLanguage::resolve($language)) {
            CalendarLanguage::Nepali => $this->devanagari(),
            CalendarLanguage::Roman => $this->roman(),
            CalendarLanguage::English => $this->english(),
        };
    }

    /** The rashi a BS month (1–12) maps to. */
    public static function fromMonth(int $month): self
    {
        if ($month < 1 || $month > 12) {
            throw new InvalidNepaliMonthException("Invalid Nepali month {$month}. Expected 1-12.");
        }

        return self::cases()[$month - 1];
    }
}
