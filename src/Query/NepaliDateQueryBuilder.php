<?php

declare(strict_types=1);

namespace Sambat\NepaliCalendar\Query;

use Illuminate\Database\Eloquent\Builder;
use Sambat\NepaliCalendar\NepaliDate;

/**
 * Query helpers that translate BS predicates onto canonical AD-backed columns.
 *
 * These are registered as macros on Eloquent's Builder, so instead of
 * converting dates by hand you can write:
 *
 *     Report::whereNepaliDate('bill_date', '2081-11-05')->get();
 *     Report::whereNepaliYear('bill_date', 2081)->get();
 *
 * The column must store the Gregorian (AD) date — the recommended canonical
 * storage — and the helper converts the BS value to its AD equivalent.
 */
final class NepaliDateQueryBuilder
{
    /** Exact day match: WHERE DATE(column) = AD equivalent. */
    public static function whereNepaliDate(Builder $query, string $column, mixed $date): Builder
    {
        return $query->whereDate($column, NepaliDate::parse($date)->ad()->toDateString());
    }

    /** All rows whose AD date falls inside the given BS year. */
    public static function whereNepaliYear(Builder $query, string $column, int $year): Builder
    {
        return $query->whereBetween($column, self::yearBounds($year));
    }

    /** All rows whose AD date falls inside the given BS month. */
    public static function whereNepaliMonth(Builder $query, string $column, int $year, int $month): Builder
    {
        $start = NepaliDate::parse(sprintf('%04d-%02d-01', $year, $month));

        return $query->whereBetween($column, [
            $start->ad()->toDateString(),
            $start->endOfMonth()->ad()->toDateString(),
        ]);
    }

    /** All rows whose AD date falls on the given BS day. */
    public static function whereNepaliDay(Builder $query, string $column, int $year, int $month, int $day): Builder
    {
        return $query->whereDate($column, (new NepaliDate($year, $month, $day))->ad()->toDateString());
    }

    /** All rows whose AD date falls inside an inclusive BS range. */
    public static function whereNepaliBetween(Builder $query, string $column, mixed $start, mixed $end): Builder
    {
        $start = NepaliDate::parse($start);
        $end = NepaliDate::parse($end);

        if ($start->isAfter($end)) {
            [$start, $end] = [$end, $start];
        }

        return $query->whereBetween($column, [
            $start->ad()->toDateString(),
            $end->ad()->toDateString(),
        ]);
    }

    /** Order a column (canonical AD storage sorts naturally) by BS semantics. */
    public static function orderByNepaliDate(Builder $query, string $column, string $direction = 'asc'): Builder
    {
        return $query->orderBy($column, strtolower($direction) === 'desc' ? 'desc' : 'asc');
    }

    /** @return array{0: string, 1: string} inclusive AD bounds of a BS year */
    private static function yearBounds(int $year): array
    {
        $start = NepaliDate::parse(sprintf('%04d-01-01', $year));

        return [
            $start->ad()->toDateString(),
            $start->endOfYear()->ad()->toDateString(),
        ];
    }
}
