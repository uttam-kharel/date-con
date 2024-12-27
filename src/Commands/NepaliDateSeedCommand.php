<?php

declare(strict_types=1);

namespace Sambat\NepaliCalendar\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Sambat\NepaliCalendar\Constants\CalendarData;

class NepaliDateSeedCommand extends Command
{
    protected $signature = 'nepali:seed
        {--fresh : Delete existing rows before seeding}
        {--connection= : The database connection to seed}';

    protected $description = 'Populate the Nepali calendar years table used by the database driver.';

    public function handle(): int
    {
        $table = (string) config('nepali-calendar.database_table', 'nepali_calendar_years');

        try {
            $db = app('db')->connection($this->option('connection') ?: null);
        } catch (\Throwable $e) {
            $this->error('No database connection available: '.$e->getMessage());

            return self::FAILURE;
        }

        if (! $db->getSchemaBuilder()->hasTable($table)) {
            $this->error("Table [{$table}] does not exist. Publish and run the migrations first:");
            $this->line('php artisan vendor:publish --tag=nepali-calendar-migrations');
            $this->line('php artisan migrate');

            return self::FAILURE;
        }

        $existing = (int) $db->table($table)->count();

        if ($existing > 0 && ! $this->option('fresh')) {
            $this->error("Table [{$table}] already contains {$existing} rows. Re-run with --fresh to replace them.");

            return self::FAILURE;
        }

        $data = CalendarData::NEPALI_YEARS;
        $now = Carbon::now();

        $db->transaction(function () use ($db, $table, $data, $now): void {
            $db->table($table)->delete();

            foreach ($data as $year => $months) {
                $db->table($table)->insert([
                    'bs_year' => $year,
                    'months' => json_encode($months),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        });

        $this->info(sprintf(
            'Seeded %d BS years (%d - %d) into [%s].',
            count($data),
            min(array_keys($data)),
            max(array_keys($data)),
            $table
        ));

        return self::SUCCESS;
    }
}
