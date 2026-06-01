<?php

declare(strict_types=1);

namespace Sambat\NepaliCalendar\Holidays;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use JsonSerializable;
use Sambat\NepaliCalendar\Exceptions\InvalidNepaliDateException;
use Sambat\NepaliCalendar\NepaliDate;
use Stringable;

/**
 * A single holiday on a BS date.
 *
 * Holidays are plain value objects — the package never hardcodes festive
 * dates into the core. Supply them through config, a HolidayRepository, or
 * the container binding 'nepali-calendar.holidays'.
 */
final class NepaliHoliday implements Arrayable, Jsonable, JsonSerializable, Stringable
{
    public function __construct(
        private readonly NepaliDate $date,
        private readonly string $name = '',
        private readonly string $type = 'custom',
    ) {}

    /**
     * Build from loose config values:
     *  - '2083-01-01'                       (date string)
     *  - ['date' => '2083-01-01', 'name' => 'New Year', 'type' => 'national']
     *  - ['2083-01-01', 'New Year']         (list form)
     */
    public static function fromConfig(mixed $value, ?string $fallbackName = null): self
    {
        if (is_string($value)) {
            return new self(NepaliDate::parse($value), $fallbackName ?? '');
        }

        if (is_array($value)) {
            $date = $value['date'] ?? $value[0] ?? null;
            $name = $value['name'] ?? ($value[1] ?? ($fallbackName ?? ''));
            $type = $value['type'] ?? 'custom';

            if ($date === null) {
                throw InvalidNepaliDateException::forValue($value);
            }

            return new self(NepaliDate::parse($date), (string) $name, (string) $type);
        }

        throw InvalidNepaliDateException::forValue($value);
    }

    public function date(): NepaliDate
    {
        return $this->date;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function type(): string
    {
        return $this->type;
    }

    public function toArray(): array
    {
        return [
            'date' => $this->date->toDateString(),
            'name' => $this->name,
            'type' => $this->type,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function toJson($options = 0): string
    {
        return json_encode($this->toArray(), $options);
    }

    public function __toString(): string
    {
        return $this->name !== '' ? $this->name : $this->date->toDateString();
    }
}
