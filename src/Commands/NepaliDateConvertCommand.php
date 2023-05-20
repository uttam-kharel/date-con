<?php

declare(strict_types=1);

namespace Sambat\NepaliCalendar\Commands;

use Illuminate\Console\Command;
use Sambat\NepaliCalendar\NepaliDate;

class NepaliDateConvertCommand extends Command
{
    protected $signature = 'nepali:convert
        {date : The date to convert (e.g. 2025-02-17 or 2081-11-28)}
        {--from=ad : Input calendar: ad|bs}
        {--to= : Output calendar: ad|bs (defaults to the opposite of --from)}
        {--format=Y-m-d : Output format (Y, m, d, F, l, ... with Devanagari support)}';

    protected $description = 'Convert a date between the Gregorian (AD) and Nepali (BS) calendars.';

    public function handle(): int
    {
        $from = strtolower($this->option('from'));
        $to = strtolower((string) ($this->option('to') ?: ($from === 'ad' ? 'bs' : 'ad')));
        $format = $this->option('format');

        if (! in_array($from, ['ad', 'bs'], true) || ! in_array($to, ['ad', 'bs'], true)) {
            $this->error('--from and --to must be one of: ad, bs');

            return self::FAILURE;
        }

        try {
            $date = $from === 'ad'
                ? NepaliDate::fromAd($this->argument('date'))
                : NepaliDate::parse($this->argument('date'));

            $output = $to === 'ad' ? $date->formatAd($format) : $date->format($format);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->line($output);

        return self::SUCCESS;
    }
}
