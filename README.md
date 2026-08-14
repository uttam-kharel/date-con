# 📅 Nepali Calendar (sambat/nepali-calendar)

[![PHP](https://img.shields.io/badge/PHP-8.1%20%E2%80%93%208.5-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![Laravel](https://img.shields.io/badge/Laravel-10%20%E2%80%93%2012-FF2D20?logo=laravel&logoColor=white)](https://laravel.com/)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE.md)
[![Code style](https://img.shields.io/badge/code%20style-Laravel%20Pint-FF2D20)](https://laravel.com/docs/pint)

**The god-tier Nepali (Bikram Sambat) date & time package for Laravel and plain PHP.**

One package that replaces and supersedes every other Nepali date package:

```php
use Sambat\NepaliCalendar\NepaliDate;

$date = NepaliDate::parse('2081-11-05');            // BS-first, or from an AD value

echo $date;                                          // २०८१-११-०५
echo $date->format('l, F j, Y');                     // सोमबार, फागुन ५, २०८१
echo $date->format('l, F j, Y', 'english');          // Monday, February 17, 2025
echo $date->ad()->format('Y-m-d');                   // 2025-02-17
echo $date->addDays(30)->addMonths(1);               // immutable fluent arithmetic
echo $date->diffForHumans();                         // १ वर्ष अघि / "1 year ago"
```

- ✅ AD ⇄ BS conversion, verified against the real Nepali calendar
- ✅ Immutable, Carbon-style value object with full arithmetic & diffs
- ✅ Devanagari numerals (२०८१) and Nepali / Romanized / English names
- ✅ Laravel validation rules, Blade directives, Facade, artisan commands
- ✅ **Choose your data source** — built-in algorithm (no DB, works anywhere) or your own database table
- ✅ Date ranges, fiscal years (2083/84) & quarters — built for reports & billing
- ✅ Business days & configurable holidays (no hardcoded festive dates)
- ✅ Eloquent casts, ranged validation rules & query helpers for Laravel
- ✅ Named format presets (SHORT / MEDIUM / LONG / FULL, date & time)
- ✅ 128 tests / 2600+ assertions, O(1) conversions

---

## Why this package exists (the study)

Two packages were studied in detail: **`hmis/nepali-date`** (a DB-backed converter)
and **`mr.incognito/date-converter`** (an algorithm-based converter).

| Area | `hmis/nepali-date` | `mr.incognito/date-converter` | **This package** |
|---|---|---|---|
| Data source | MySQL `dates` table + 52k-row SQL dump + seeder | hardcoded 2000–2099 table | hardcoded 2000–2099 table, verified |
| Conversion | `LIKE` query + `Cache::rememberForever` | day-by-day while loops (O(n)) | precomputed cumulative days (O(1)) |
| Returns | `?string` or `null` on miss | `string` or raw exception **message string** | typed `NepaliDate` object, typed exceptions |
| Range | depends on SQL dump (checked: 1964–2058 AD) | AD 1944–2033 only (data has more) | **BS 2000–2099 / AD 1943–2043** |
| Weekday / month names | ✗ | ✓ (Devanagari only) | ✓ Nepali + Romanized + English |
| Devanagari numerals | ✗ | ✗ | ✓ |
| Formatting | none (fixed `Y-m-d`) | only `Y`, `m`, `d` via `str_replace` | **full PHP `date()` token set** + escapes |
| Arithmetic (add/sub/diff) | ✗ | ✗ | ✓ (immutable, clamping) |
| `startOf`/`endOf`, calendar grid | ✗ | ✗ | ✓ |
| Diff for humans / age | ✗ | ✗ | ✓ (Nepali & English) |
| Validation rules | ✗ | ✗ | ✓ (`nepali_date`, `nepali_date_format`) |
| Blade directives | ✗ | ✗ | ✓ (`@nepaliDate`, `@nepaliDateHuman`) |
| Carbon integration | ✗ | ✗ | ✓ macros |
| Artisan commands | `nepali-date:update` (needs DB) | ✗ | `nepali:convert`, `nepali:info`, `nepali:seed` |
| Parsing flexibility | exact `YYYY-MM-DD` only | `str_replace`-based, brittle | many formats + month names + Devanagari input |
| Database required | **yes** (migration + seed) | no | **optional** (`database` driver, default `algorithm`) |
| Test coverage | none | ~12 tests | **81 tests, 2400+ assertions** |

### Problems found in the existing packages

1. **`hmis/nepali-date` requires a database** just to convert dates. Every install needs a
   migration + a 52k-row `dates.sql` seed (`php artisan nepali-date:update` truncates and re-inserts).
   The conversion is a `LIKE '2025-%'` query; it silently returns `null` when a date is missing
   or out of range instead of throwing.
2. **`hmis/nepali-date` README example is factually wrong**: it claims `toBs('2025-02-17')` is
   `'2081-10-03'`, but the real Nepali calendar (and its own `dates.sql`!) says `2081-11-05`.
   An entire doc example was copy-pasted from a wrong source.
3. **`mr.incognito/date-converter` returns error strings from successful-looking methods.**
   `currentBsDate()` returns `$e->getMessage()` on failure — callers can't distinguish a date
   from an error. It even catches and swallows `Exception` around every call.
4. **`date-converter` validates ranges smaller than its own data** (AD 1944–2033 while the table
   holds 1943–2043; BS 2000–2089 while the table holds 2090–2099).
5. **`date-converter`'s format helper only knows `Y`, `m`, `d`** — no day names, month names,
   ordinals, no way to escape, and parsing is a naive `str_replace` on `Y-m-d`.
6. **`DateValidationHelper::isInRangeEng()`** declares `bool|string` but returns the
   `InvalidArgumentException` *object* — the type contract is broken.
7. **The day-by-day loop algorithm is O(n)** (~60k iterations per conversion in the worst case
   for `toNepaliDate`) and recomputes from scratch every call.
8. No numerals support, no arithmetic, no diffs, no validation, no Blade, no commands, no tests
   (`nepali-date` has no tests at all).

---

## Installation

```bash
composer require sambat/nepali-calendar
```

Laravel auto-discovers the service provider and facade. For plain PHP projects the package
works out of the box too — no database, no setup.

Publish the config (optional):

```bash
php artisan vendor:publish --tag=nepali-calendar-config
```

```php
// config/nepali-calendar.php
return [
    'driver'         => env('NEPALI_CALENDAR_DRIVER', 'algorithm'), // algorithm | database
    'database_table' => env('NEPALI_CALENDAR_TABLE', 'nepali_calendar_years'),
    'language'       => 'nepali',    // nepali | roman | english
    'numerals'       => 'devanagari',// devanagari | english
    'week_starts_on' => 'sunday',    // sunday | monday
    'default_format' => 'Y-m-d',
    'weekend'        => [6],          // PHP weekday numbers: 0 = Sunday ... 6 = Saturday
    'holidays'       => [
        '2083-01-01' => 'Nepali New Year',
        ['date' => '2083-10-15', 'name' => 'Dashain', 'type' => 'national'],
    ],
];
```

---

## Data source: algorithm or database

By default the package ships its calendar data (BS 2000–2099) and needs no database.
If your app prefers to own the data, switch the driver and the same month table is read
from a database table instead — **one row per BS year** (months stored as JSON, ~100 rows),
not the 52,816 per-date rows other DB-backed packages require.

| Driver | Where the data lives | Setup |
|---|---|---|
| `algorithm` (default) | built-in, observation-verified constant table | nothing |
| `database` | `nepali_calendar_years` table | publish migrations + `nepali:seed` |

### Using the database driver

```bash
# 1. Publish config and migrations
php artisan vendor:publish --tag=nepali-calendar-config
php artisan vendor:publish --tag=nepali-calendar-migrations

# 2. Run migrations and seed the table
php artisan migrate
php artisan nepali:seed                    # 100 BS years (2000 - 2099)

# 3. Switch the driver
NEPALI_CALENDAR_DRIVER=database
```

Set it in `.env`, or edit the published config directly. Verify with
`php artisan about` (Nepali Calendar section) or `php artisan nepali:info`, which prints the
active **Data source**.

Data maintenance:

```bash
php artisan nepali:seed --fresh            # replace existing data
php artisan nepali:seed --connection=mysql # seed a specific connection
```

`nepali:seed` refuses to run when the table already has rows (use `--fresh`), and the
database driver validates the table on first use: it must contain a contiguous range of
BS years starting at 2000, otherwise you get a clear error pointing at `nepali:seed`.
Empty or partial tables never silently return wrong dates. The table is read once per
request and cached in memory.

### Custom data sources

The engine reads data through the `Sambat\NepaliCalendar\Contracts\CalendarDataProvider`
interface, so the calendar data can come from anywhere — an API, a file, another table,
an extended year range:

```php
// AppServiceProvider::register()
app()->singleton('nepali-calendar.provider', fn () => new MyApiCalendarDataProvider);

// or swap at runtime / in tests
use Sambat\NepaliCalendar\Calendar;
Calendar::setProvider(new MyApiCalendarDataProvider);
```

---

## Usage

### Conversion (facade / helpers / class)

```php
use Sambat\NepaliCalendar\NepaliDate;
use Sambat\NepaliCalendar\Facades\NepaliDate as NepaliDateFacade;

$date  = NepaliDate::fromAd('2025-02-17');           // AD -> BS
$date  = NepaliDateFacade::fromAd('2025-02-17');     // same via facade
$ad    = NepaliDate::parse('2081-11-05')->ad();      // BS -> Carbon

ad_to_bs('2025-02-17');       // '२०८१-११-०५'
bs_to_ad('2081-11-05');       // '2025-02-17'
bs_today();                   // today in BS
```

### Parsing is flexible

```php
NepaliDate::parse('2081-11-28');         // YYYY-MM-DD
NepaliDate::parse('2081/11/28');         // any separator
NepaliDate::parse('20811128');           // compact
NepaliDate::parse('२०८१-११-२८');         // Devanagari digits
NepaliDate::parse('2081 Falgun 28');     // romanized month name
NepaliDate::parse('2081-फागुन-28');       // Devanagari month name
NepaliDate::parse('2081-11-28 14:30:45');// with time
NepaliDate::parse(['year' => 2081, 'month' => 11, 'day' => 28]);
NepaliDate::parse([2081, 11, 28]);
```

### Formatting

Full PHP `date()` token set, plus Devanagari numerals and multi-language names:

```php
$date = NepaliDate::parse('2081-11-05');   // Monday 2025-02-17

$date->format('Y-m-d');                    // २०८१-११-०५ (devanagari by default)
$date->format('Y-m-d', null, false);       // 2081-11-05 (western digits)
$date->format('l, F j, Y');                // सोमबार, फागुन ५, २०८१
$date->format('l, F j, Y', 'roman');       // Sombaar, Falgun 5, 2081
$date->format('l, F j, Y', 'english');     // Monday, February 17, 2025
$date->format('jS F Y');                   // ५th फागुन २०८१ (ordinals stay Latin)
$date->format('Y \y\e\a\r');               // २०८१ year (escaped literals)
$date->format('H:i:s');                    // time from the AD instant
```

Supported tokens: `Y y m n M F d j S D l N w z t L W` plus time tokens
`g G h H i s A a U e O P T c r`. `z` is BS day-of-year (0-based), `t` days in the BS month,
`L` 1 when the BS year has 366 days, `W` week number within the BS year.

Named presets (via the `DateFormat` enum) render the common shapes without
remembering token strings:

```php
use Sambat\NepaliCalendar\Enums\DateFormat;

$date->formatPreset(DateFormat::SHORT);          // २०८१-११-०५
$date->formatPreset(DateFormat::LONG);           // फागुन ५, २०८१
$date->formatPreset(DateFormat::FULL);           // सोमबार, फागुन ५, २०८१
$date->formatPreset(DateFormat::FULL, 'english', false); // Monday, February 17, 2025
$date->formatPreset(DateFormat::DATETIME_SHORT); // २०८१-११-०५ १४:३०
$date->formatPreset(DateFormat::TIME_FULL);      // २:३०:४५ PM
```

### Arithmetic & diffs (immutable)

```php
$d = NepaliDate::parse('2081-11-05');

$d->addDays(10)->subDays(1)->addMonths(2)->addYears(1)->addWeeks(3);
$d->nextDay()->previousMonth()->nextYear();
$d->addMonths(1);                    // month arithmetic clamps to the target month length

$d->diffInDays(NepaliDate::parse('2081-12-05'));   // 30
$d->diffInMonths(...); $d->diffInYears(...);       // full months / years
NepaliDate::parse('2000-01-01')->age();            // 81 (full years to today)
$d->diffForHumans();                               // '१ वर्ष अघि' / '1 year ago'
```

### Periods & calendar grids

```php
$d->startOfMonth(); $d->endOfMonth();       // 2081-11-01 / 2081-11-29
$d->startOfYear();  $d->endOfYear();        // 2081-01-01 / 2081-12-31
$d->startOfQuarter(); $d->endOfQuarter();
$d->startOfWeek();  $d->endOfWeek();        // respects config week_starts_on

foreach ($d->calendar() as $week) {         // ready-made month grid for UI calendars
    foreach ($week as $slot) { /* NepaliDate|null */ }
}
```

### Date ranges & fiscal year

```php
use Sambat\NepaliCalendar\NepaliDateRange;
use Sambat\NepaliCalendar\NepaliFiscalYear;

$range = NepaliDateRange::between('2081-07-01', '2081-09-30'); // inclusive
$range->count();            // 91
$range->contains('2081-08-15');
foreach ($range as $day) { /* NepaliDate */ }
$range->days(); $range->weeks(); $range->months(); $range->years();

$fy = NepaliFiscalYear::fromDate('2083-08-15'); // Shrawan-based fiscal year
$fy->label();               // '2083/84'
$fy->startDate();           // 2083-04-01 (Shrawan 1)
$fy->endDate();             // Ashadh 31 of 2084
$fy->quarter();             // 1-4, defaults to now
$fy->quarterRange(2);       // NepaliDateRange

NepaliDate::parse('2083-08-15')->fiscalQuarter();   // 2
NepaliDate::parse('2083-08-15')->fiscalYear()->label(); // '2083/84'
NepaliDate::parse('2083-08-15')->startOfFiscalYear();
NepaliDate::parse('2083-08-15')->rangeTo('2083-12-31');
```

### Business days & holidays

Holidays come from **your** config or container — the core never hardcodes
festive dates:

```php
// config/nepali-calendar.php
'weekend' => [6],                // Saturday
'holidays' => [
    '2083-01-01' => 'Nepali New Year',
    ['date' => '2083-10-15', 'name' => 'Dashain', 'type' => 'national'],
],

// or bind your own source
app()->singleton('nepali-calendar.holidays', fn () => new HolidayRepository(...));
```

```php
$d = NepaliDate::parse('2083-08-15');

$d->isWeekend();                 // Saturday by default, configurable
$d->isHoliday();                 // matches the configured holidays
$d->isBusinessDay();             // not weekend and not holiday
$d->addBusinessDays(10);         // skips weekends and holidays
$d->businessDaysUntil('2083-09-01'); // signed business-day gap
```

### Comparison

```php
$d->isBefore($other); $d->isAfter($other); $d->isBetween($a, $b);
$d->isSameDay($other); $d->equals('2081-11-05');
$d->isToday(); $d->isPast(); $d->isFuture(); $d->isWeekend();
```

### Laravel validation

```php
'birth_date' => ['required', 'nepali_date'],
'dob'        => ['required', 'nepali_date_format:Y-m-d'],

// ranged rules
'end_date' => ['required', 'nepali_date_before:2083-04-01'],
'start_date' => ['required', 'nepali_date_after:2083-01-01'],
'event_date' => ['required', 'nepali_date_between:2083-01-01,2083-12-31'],

// or rule classes
use Sambat\NepaliCalendar\Rules\NepaliDateRule;
use Sambat\NepaliCalendar\Rules\NepaliDateFormatRule;
use Sambat\NepaliCalendar\Rules\NepaliDateBeforeRule;
use Sambat\NepaliCalendar\Rules\NepaliDateAfterRule;
use Sambat\NepaliCalendar\Rules\NepaliDateBetweenRule;

'date' => ['required', new NepaliDateRule],
'date' => ['required', new NepaliDateFormatRule('Y-m-d')],
'date' => ['required', new NepaliDateBeforeRule('2083-04-01')],
'date' => ['required', new NepaliDateAfterRule('2083-01-01')],
'date' => ['required', new NepaliDateBetweenRule('2083-01-01', '2083-12-31')],
```

### Eloquent casts

Store the canonical Gregorian date; present BS everywhere in your app:

```php
use Sambat\NepaliCalendar\Casts\NepaliDateCast;
use Sambat\NepaliCalendar\Casts\NepaliDateTimeCast;

protected $casts = [
    'bill_date' => NepaliDateCast::class,        // date-only
    'appointment_at' => NepaliDateTimeCast::class, // keeps the time
];

$invoice->bill_date;                 // NepaliDate
$invoice->bill_date->format('l, F j, Y');  // सोमबार, फागुन ५, २०८१
$invoice->bill_date = '2081-11-05';  // or NepaliDate | Carbon | array
```

### Query helpers

Query AD-backed columns with BS predicates:

```php
Report::whereNepaliDate('bill_date', '2081-11-05')->get();
Report::whereNepaliYear('bill_date', 2081)->get();
Report::whereNepaliMonth('bill_date', 2081, 11)->get();
Report::whereNepaliDay('bill_date', 2081, 11, 5)->get();
Report::whereNepaliBetween('bill_date', '2081-07-01', '2081-09-30')->get();
Report::orderByNepaliDate('bill_date', 'desc')->get();
```

### Blade

```blade
@nepaliDate($user->birth_date)                      {{-- २०८१-११-०५ --}}
@nepaliDate($user->birth_date, 'l, F j, Y')         {{-- सोमबार, फागुन ५, २०८१ --}}
@nepaliDateHuman($user->birth_date)                 {{-- १ वर्ष अघि --}}
```

### Carbon macros

```php
Carbon::parse('2025-02-17')->toNepaliDate();        // NepaliDate
Carbon::parse('2025-02-17')->formatNepali('Y-m-d'); // २०८१-११-०५
```

### Artisan

```bash
php artisan nepali:convert 2025-02-17               # २०८१-११-०५
php artisan nepali:convert 2081-11-05 --from=bs --to=ad
php artisan nepali:info 2081-11-05 --language=roman # full breakdown
php artisan nepali:info                             # today's breakdown
php artisan nepali:seed                             # populate the DB driver's table
php artisan nepali:seed --fresh                     # replace existing data
```

### Helpers

```php
nepali_date('2081-11-05')      // NepaliDate object
ad_to_bs('2025-02-17')         // '२०८१-११-०५'
bs_to_ad('2081-11-05')         // '2025-02-17'
bs_today()                     // today in BS
nepali_number('2081')          // '२०८१'
english_number('२०८१')          // '2081'
bs_days_in_month(2081, 11)     // 29
bs_days_in_year(2081)          // 366
bs_is_leap_year(2081)          // true
bs_age('2000-01-01')           // full years
bs_diff_for_humans($from, $to) // human diff
bs_fiscal_year('2083-08-15')   // fiscal year containing a date
bs_date_range($from, $to)      // inclusive BS date range
```

---

## Accuracy

The calendar table (BS 2000–2099) is the standard observation-based record and is anchored at
**BS 2000-01-01 = AD 1943-04-14**. Every conversion was verified against independently known
real-world dates, and the test suite round-trips sampled days across the entire range:

| BS | AD | Why it's known |
|---|---|---|
| 2080-01-01 | 2023-04-14 | Nepali New Year 2080 |
| 2081-01-01 | 2024-04-13 | Nepali New Year 2081 |
| 2082-01-01 | 2025-04-14 | Nepali New Year 2082 |
| 2083-01-01 | 2026-04-14 | Nepali New Year 2083 |
| 2081-09-01 | 2024-12-16 | Poush 1, 2081 |
| 2081-10-03 | 2025-01-16 | Magh 3, 2081 (Maghe Sankranti week) |
| 2081-11-05 | 2025-02-17 | Monday 2025-02-17 |

## Supported range

- BS: **2000-01-01 … 2099-12-30**
- AD: **1943-04-14 … 2043-04-13**

Dates outside the range throw a typed `NepaliDateOutOfRangeException` (an
`InvalidNepaliDateException`).

## Testing

```bash
composer test        # 128 tests, 2600+ assertions
composer pint        # Laravel code style
```

Supported PHP versions (**8.1 – 8.5**) and Laravel versions (10 – 12) are verified in CI
on every push — see [`.github/workflows/tests.yml`](.github/workflows/tests.yml).

## Contributing

Contributions are welcome. Please read [CONTRIBUTING.md](CONTRIBUTING.md) first, keep the
[test suite](CONTRIBUTING.md#development-workflow) green, and check the
[CHANGELOG.md](CHANGELOG.md) for what changed between releases. Report security issues
privately — see [SECURITY.md](SECURITY.md). See [ROADMAP.md](ROADMAP.md) for the
version-by-version feature plan.

## Credits & license

Inspired by (and written after studying) `hmis/nepali-date` and `mr.incognito/date-converter`,
whose shared month table this package builds upon. MIT licensed — see [LICENSE.md](LICENSE.md).
