<?php

declare(strict_types=1);

namespace Sambat\NepaliCalendar\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Sambat\NepaliCalendar\NepaliDate;

/**
 * Cast an Eloquent attribute to a NepaliDate while preserving the time of
 * day (the BS date object always knows its equivalent Gregorian instant).
 *
 *     protected $casts = ['appointment_at' => NepaliDateTimeCast::class];
 *
 *     $model->appointment_at;            // NepaliDate with time kept
 *     $model->appointment_at->ad()->format('H:i');   // 14:30
 *     $model->appointment_at = '2081-11-05 14:30:00';
 */
final class NepaliDateTimeCast implements CastsAttributes
{
    public function __construct(private readonly string $format = 'Y-m-d H:i:s') {}

    public function get($model, string $key, $value, array $attributes): ?NepaliDate
    {
        if ($value === null || $value === '') {
            return null;
        }

        return NepaliDate::fromAd($value);
    }

    public function set($model, string $key, $value, array $attributes): array
    {
        if ($value === null) {
            return [$key => null];
        }

        $date = $value instanceof NepaliDate ? $value : NepaliDate::parse($value);

        // Store the canonical Gregorian datetime; the model presents BS on read.
        return [$key => $date->ad()->format($this->format)];
    }
}
