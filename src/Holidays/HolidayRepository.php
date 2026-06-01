<?php

declare(strict_types=1);

namespace Sambat\NepaliCalendar\Holidays;

use Sambat\NepaliCalendar\NepaliDate;
use Sambat\NepaliCalendar\Support\Config;

/**
 * The application's holiday calendar.
 *
 * By default holidays are read from the `holidays` config array. Applications
 * that maintain their own holiday data can either:
 *
 *  - bind a HolidayRepository as 'nepali-calendar.holidays' in the container, or
 *  - swap it at runtime / in tests with HolidayRepository::setInstance().
 *
 * The package never hardcodes festive dates into the core.
 */
final class HolidayRepository
{
    private static ?self $instance = null;

    private readonly HolidayCollection $collection;

    /** @param iterable<NepaliHoliday> $holidays */
    public function __construct(iterable $holidays = [])
    {
        $this->collection = new HolidayCollection(
            is_array($holidays) ? array_values($holidays) : iterator_to_array($holidays, false)
        );
    }

    /** The active repository (container binding > config, cached). */
    public static function instance(): self
    {
        if (self::$instance !== null) {
            return self::$instance;
        }

        if (function_exists('app') && app()->bound('nepali-calendar.holidays')) {
            return self::$instance = app('nepali-calendar.holidays');
        }

        return self::$instance = self::fromConfig();
    }

    /** Replace the active repository (null restores config resolution). */
    public static function setInstance(?self $instance): void
    {
        self::$instance = $instance;
    }

    /** Build from the `nepali-calendar.holidays` config array. */
    public static function fromConfig(): self
    {
        $config = Config::get('nepali-calendar.holidays', []);

        return is_array($config) ? self::fromArray($config) : new self;
    }

    /**
     * Build from loose config:
     *
     *  [
     *      '2083-01-01' => 'Nepali New Year',
     *      ['date' => '2083-10-15', 'name' => 'Dashain', 'type' => 'national'],
     *      '2083-05-15',
     *  ]
     */
    public static function fromArray(array $config): self
    {
        $holidays = [];

        foreach ($config as $key => $value) {
            if (is_string($key)) {
                // '2083-01-01' => 'New Year' | ['name' => ..., 'type' => ...]
                $name = is_string($value) ? $value : (is_array($value) ? ($value['name'] ?? '') : '');
                $type = is_array($value) ? ($value['type'] ?? 'custom') : 'custom';
                $holidays[] = new NepaliHoliday(NepaliDate::parse($key), (string) $name, (string) $type);

                continue;
            }

            $holidays[] = NepaliHoliday::fromConfig($value);
        }

        return new self($holidays);
    }

    public function all(): HolidayCollection
    {
        return $this->collection;
    }

    public function forYear(int $year): HolidayCollection
    {
        return $this->collection->forYear($year);
    }

    public function contains(NepaliDate $date): bool
    {
        return $this->collection->contains($date);
    }

    public function forDate(NepaliDate $date): ?NepaliHoliday
    {
        return $this->collection->forDate($date);
    }
}
