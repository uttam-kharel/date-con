<?php

declare(strict_types=1);

namespace Sambat\NepaliCalendar\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Sambat\NepaliCalendar\NepaliDate;

/**
 * Cast an Eloquent attribute to a NepaliDate.
 *
 * The canonical Gregorian value is stored in the database (date-only by
 * default) and presented as a BS date on the model:
 *
 *     protected $casts = ['bill_date' => NepaliDateCast::class];
 *
 *     $model->bill_date;            // NepaliDate
 *     $model->bill_date->format('l, F j, Y');
 *     $model->bill_date = '2081-11-05';   // accepts NepaliDate | string | Carbon | array
 */
final class NepaliDateCast implements CastsAttributes
{
    public function __construct(private readonly string $format = 'Y-m-d') {}

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

        // Store the canonical Gregorian date (sortable, queryable, timezone
        // safe); the model presents the BS date on read.
        return [$key => $date->ad()->format($this->format)];
    }
}
