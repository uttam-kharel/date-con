<?php

declare(strict_types=1);

namespace Sambat\NepaliCalendar\Commands;

use Illuminate\Console\Command;
use Sambat\NepaliCalendar\NepaliDate;

class NepaliDateInfoCommand extends Command
{
    protected $signature = 'nepali:info
        {date? : An AD or BS date (BS assumed). Defaults to today.}
        {--ad : Treat the input as an AD (Gregorian) date}
        {--language=nepali : Name language: nepali|roman|english}';

    protected $description = 'Show detailed information about a Nepali (Bikram Sambat) date.';

    public function handle(): int
    {
        $date = $this->argument('date');

        try {
            $nepali = $date === null
                ? NepaliDate::now()
                : ($this->option('ad') ? NepaliDate::fromAd($date) : NepaliDate::parse($date));
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $language = $this->option('language');

        $this->components->twoColumnDetail('BS date', $nepali->format('Y-m-d l', $language));
        $this->components->twoColumnDetail('AD date', $nepali->formatAd('Y-m-d l'));
        $this->components->twoColumnDetail('Year', (string) $nepali->year());
        $this->components->twoColumnDetail('Month', $nepali->monthName($language).' ('.$nepali->month().')');
        $this->components->twoColumnDetail('Day', (string) $nepali->day());
        $this->components->twoColumnDetail('Week day', $nepali->weekDayName($language).' ('.$nepali->weekDay().')');
        $this->components->twoColumnDetail('Day of year', (string) $nepali->dayOfYear());
        $this->components->twoColumnDetail('Week of year', (string) $nepali->weekOfYear());
        $this->components->twoColumnDetail('Days in month', (string) $nepali->daysInMonth());
        $this->components->twoColumnDetail('Days in year', (string) $nepali->daysInYear());
        $this->components->twoColumnDetail('Leap year', $nepali->isLeapYear() ? 'yes' : 'no');
        $this->components->twoColumnDetail('Season', $nepali->season()->name($language));
        $this->components->twoColumnDetail('Rashi', $nepali->rashi()->name($language));
        $this->components->twoColumnDetail('Data source', ucfirst((string) config('nepali-calendar.driver', 'algorithm')));

        $this->newLine();
        $this->components->info('Example formats');
        $this->components->twoColumnDetail('l, F j, Y', $nepali->format('l, F j, Y', $language));
        $this->components->twoColumnDetail('Y-m-d (Devanagari)', $nepali->toNepaliNumerals());
        $this->components->twoColumnDetail('Human', $nepali->diffForHumans(language: $language));

        return self::SUCCESS;
    }
}
