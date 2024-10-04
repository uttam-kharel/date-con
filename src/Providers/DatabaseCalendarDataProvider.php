<?php

declare(strict_types=1);

namespace Sambat\NepaliCalendar\Providers;

use PDO;
use RuntimeException;
use Sambat\NepaliCalendar\Constants\CalendarData;
use Sambat\NepaliCalendar\Contracts\CalendarDataProvider;
use Sambat\NepaliCalendar\Support\Config;

/**
 * Database-backed calendar driver. Reads the BS month-length table from a
 * `nepali_calendar_years` table (one row per year, months stored as JSON),
 * so applications can keep the calendar data in their own database instead
 * of shipping it in the package.
 *
 * Works inside Laravel through the default connection, or in plain PHP when
 * given a PDO instance. The table must contain a contiguous range of BS
 * years starting at 2000 - populate it with `php artisan nepali:seed`.
 *
 * Data is fetched once per request and cached in memory.
 */
final class DatabaseCalendarDataProvider implements CalendarDataProvider
{
    /** @var array<int, array<int, int>>|null */
    private ?array $cache = null;

    public function __construct(private readonly ?PDO $pdo = null) {}

    public function allMonthLengths(): array
    {
        if ($this->cache !== null) {
            return $this->cache;
        }

        $data = [];
        foreach ($this->fetchRows() as $row) {
            $months = json_decode((string) $row['months'], true);

            if (! is_array($months) || count($months) !== 12) {
                throw new RuntimeException(
                    "Invalid calendar data for BS year [{$row['bs_year']}] in table [{$this->table()}]: "
                    .'expected a JSON array of 12 month lengths.'
                );
            }

            $data[(int) $row['bs_year']] = array_map('intval', $months);
        }

        if ($data === []) {
            throw new RuntimeException(
                "The Nepali calendar table [{$this->table()}] is empty. "
                .'Run `php artisan nepali:seed` to populate it.'
            );
        }

        $min = CalendarData::BS_MIN_YEAR;
        $max = max(array_keys($data));

        if (min(array_keys($data)) !== $min || count($data) !== $max - $min + 1) {
            throw new RuntimeException(
                "The Nepali calendar table [{$this->table()}] must contain a contiguous range of BS years "
                ."starting at {$min} (found {$min}-".max(array_keys($data)).' with '.count($data).' years). '
                .'Run `php artisan nepali:seed --fresh` to repopulate it.'
            );
        }

        return $this->cache = $data;
    }

    public function minYear(): int
    {
        return min(array_keys($this->allMonthLengths()));
    }

    public function maxYear(): int
    {
        return max(array_keys($this->allMonthLengths()));
    }

    private function table(): string
    {
        return (string) Config::get('nepali-calendar.database_table', 'nepali_calendar_years');
    }

    /**
     * @return array<int, array{bs_year: int, months: string}>
     */
    private function fetchRows(): array
    {
        if ($this->pdo !== null) {
            $statement = $this->pdo->query(sprintf('SELECT bs_year, months FROM %s', $this->table()));

            return $statement !== false ? $statement->fetchAll(PDO::FETCH_ASSOC) : [];
        }

        if (function_exists('app') && app()->bound('db')) {
            $rows = app('db')->table($this->table())->get(['bs_year', 'months']);

            return array_map(
                static fn ($row): array => ['bs_year' => (int) $row->bs_year, 'months' => (string) $row->months],
                $rows->all()
            );
        }

        throw new RuntimeException(
            'The database calendar driver needs a database connection: pass a PDO instance to '
            .self::class.' or use it inside a Laravel application.'
        );
    }
}
