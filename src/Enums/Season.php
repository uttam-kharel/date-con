<?php

declare(strict_types=1);

namespace Sambat\NepaliCalendar\Enums;

use Sambat\NepaliCalendar\Exceptions\InvalidNepaliMonthException;

/**
 * The six classical Nepali seasons (ऋतु), each spanning two BS months.
 *
 *     Grishma (ग्रीष्म)   Baisakh – Jestha      (months 1–2)
 *     Barsha  (वर्षा)    Ashadh  – Shrawan     (months 3–4)
 *     Sharad  (शरद)      Bhadra  – Ashwin      (months 5–6)
 *     Hemanta (हेमन्त)   Kartik  – Mangsir     (months 7–8)
 *     Shishir (शिशिर)    Poush   – Magh        (months 9–10)
 *     Vasanta (वसन्त)    Falgun  – Chaitra     (months 11–12)
 */
enum Season: string
{
    case Grishma = 'grishma';
    case Barsha = 'barsha';
    case Sharad = 'sharad';
    case Hemanta = 'hemanta';
    case Shishir = 'shishir';
    case Vasanta = 'vasanta';

    /** Devanagari name (ग्रीष्म, वर्षा, ...). */
    public function devanagari(): string
    {
        return match ($this) {
            self::Grishma => 'ग्रीष्म',
            self::Barsha => 'वर्षा',
            self::Sharad => 'शरद',
            self::Hemanta => 'हेमन्त',
            self::Shishir => 'शिशिर',
            self::Vasanta => 'वसन्त',
        };
    }

    /** Latin transliteration (Grishma, Barsha, ...). */
    public function roman(): string
    {
        return ucfirst($this->value);
    }

    /** Plain English descriptor (Summer, Monsoon, ...). */
    public function english(): string
    {
        return match ($this) {
            self::Grishma => 'Summer',
            self::Barsha => 'Monsoon',
            self::Sharad => 'Autumn',
            self::Hemanta => 'Pre-winter',
            self::Shishir => 'Winter',
            self::Vasanta => 'Spring',
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

    /** The season a BS month (1–12) belongs to. */
    public static function fromMonth(int $month): self
    {
        if ($month < 1 || $month > 12) {
            throw new InvalidNepaliMonthException("Invalid Nepali month {$month}. Expected 1-12.");
        }

        return self::cases()[intdiv($month - 1, 2)];
    }
}
