# Installation & configuration

## Install

```bash
composer require sambat/nepali-calendar
```

- **Laravel (10 – 13):** the service provider and facade are auto-discovered. No manual
  registration needed.
- **Plain PHP:** everything works out of the box. No database, no container, no setup.

## Version support

| Component | Supported |
|---|---|
| PHP | 8.1, 8.2, 8.3, 8.4, 8.5 |
| Laravel | 10, 11, 12, 13 (current) |
| illuminate/contracts & illuminate/support | ^10.0 – ^13.0 |
| Carbon | ^2.72 and ^3.0 |

The newest Laravel major (13) is tested in CI on PHP 8.4 and 8.5; older majors are
covered on the PHP lines they support. No code changes are needed when upgrading
between supported Laravel versions.

## Publish config (Laravel only, optional)

```bash
php artisan vendor:publish --tag=nepali-calendar-config
```

## Configuration reference

Every key has a sensible default — the package is usable with **zero configuration**.
These are the defaults:

```php
// config/nepali-calendar.php
return [

    // Where the BS month-length table comes from.
    // 'algorithm' (built-in, no DB) | 'database' (your own table)
    'driver' => env('NEPALI_CALENDAR_DRIVER', 'algorithm'),

    // Table used by the 'database' driver.
    'database_table' => env('NEPALI_CALENDAR_TABLE', 'nepali_calendar_years'),

    // Default language for month/weekday names: 'nepali' | 'roman' | 'english'
    'language' => 'nepali',

    // Digit script for format(): 'devanagari' (२०८१) | 'english' (2081)
    'numerals' => 'devanagari',

    // First day of the week for startOfWeek()/endOfWeek()/calendar(): 'sunday' | 'monday'
    'week_starts_on' => 'sunday',

    // Used by __toString() when no format is given.
    'default_format' => 'Y-m-d',

    // Non-working days, PHP weekday numbers: 0 = Sunday … 6 = Saturday.
    'weekend' => [6],                       // Nepal's standard weekend: Saturday

    // Fixed holidays used by isHoliday()/isBusinessDay(). Never hardcoded in core.
    'holidays' => [
        '2083-01-01' => 'Nepali New Year',
        ['date' => '2083-10-15', 'name' => 'Dashain', 'type' => 'national'],
    ],
];
```

### Config keys

| Key | Default | Purpose |
|---|---|---|
| `driver` | `algorithm` | `algorithm` (built-in table) or `database` (own table) |
| `database_table` | `nepali_calendar_years` | Table read by the `database` driver |
| `language` | `nepali` | Default month/weekday names (`nepali`/`roman`/`english`) |
| `numerals` | `devanagari` | Digit script rendered by `format()` |
| `week_starts_on` | `sunday` | Week boundary used by period & grid helpers |
| `default_format` | `Y-m-d` | Format used by `__toString()` |
| `weekend` | `[6]` | Weekday numbers treated as non-working |
| `holidays` | `[]` | Fixed BS holidays (date → name, or array entries) |

## Data source: algorithm or database

| Driver | Where the data lives | Setup |
|---|---|---|
| `algorithm` (default) | built-in, verified constant table (BS 2000–2100) | nothing |
| `database` | `nepali_calendar_years` table | publish migrations + `nepali:seed` |

The database driver stores **one row per BS year** (months as JSON, 101 rows) — not the
52,816 per-date rows other DB-backed packages require.

### Switching to the database driver

```bash
# 1. Publish config and migrations
php artisan vendor:publish --tag=nepali-calendar-config
php artisan vendor:publish --tag=nepali-calendar-migrations

# 2. Migrate and seed
php artisan migrate
php artisan nepali:seed                    # 101 BS years (2000–2100)

# 3. Switch the driver
NEPALI_CALENDAR_DRIVER=database
```

Set the env var in `.env`, or edit the published config directly. Verify with
`php artisan nepali:info` (prints the active **Data source**) or `php artisan about`.

Data maintenance:

```bash
php artisan nepali:seed --fresh            # replace existing rows
php artisan nepali:seed --connection=mysql # seed a specific connection
```

`nepali:seed` refuses to run when the table already has rows (use `--fresh`). The database
driver validates the table on first use: it must contain a contiguous BS range starting at
2000, otherwise you get a clear error pointing at `nepali:seed`. Empty or partial tables
never silently return wrong dates. The table is read once per request and cached.

### Custom data sources

All calendar data flows through the `Sambat\NepaliCalendar\Contracts\CalendarDataProvider`
contract, so the data can come from anywhere — an API, a file, an extended year range:

```php
use Sambat\NepaliCalendar\Calendar;

// AppServiceProvider::register() — bind once for the whole app
app()->singleton('nepali-calendar.provider', fn () => new MyApiCalendarDataProvider);

// …or swap at runtime / in tests
Calendar::setProvider(new MyApiCalendarDataProvider);
Calendar::resetProvider();   // back to the configured default
```

## Upgrading

See [CHANGELOG.md](../CHANGELOG.md) for what changed between releases. The public API is
semver-stable within the 1.x line.

Next: [Quick start](02-quick-start.md).
