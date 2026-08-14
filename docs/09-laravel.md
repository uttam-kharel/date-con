# Laravel integration

Everything from the other pages works in Laravel; this page covers the Laravel-specific
surface: validation, Eloquent casts, query helpers, Blade, Carbon macros, artisan
commands, the facade, and the global helpers.

> Supported on **Laravel 10 – 13** (including the current 13.x release line). The provider,
> facade, casts, validation rules and query macros use only stable framework APIs — the
> package is tested against each major in CI, with the newest Laravel exercised on
> PHP 8.4 / 8.5.

## Facade

```php
use Sambat\NepaliCalendar\Facades\NepaliDate;

NepaliDate::now();                    // same API as the class
NepaliDate::fromAd('2025-02-17');
```

## Validation rules

String rules (auto-registered by the service provider):

```php
'birth_date' => ['required', 'nepali_date'],
'dob'        => ['required', 'nepali_date_format:Y-m-d'],

'end_date'   => ['required', 'nepali_date_before:2083-04-01'],
'start_date' => ['required', 'nepali_date_after:2083-01-01'],
'event_date' => ['required', 'nepali_date_between:2083-01-01,2083-12-30'],
```

Or the rule classes for programmatic use / custom messages:

```php
use Sambat\NepaliCalendar\Rules\NepaliDateRule;
use Sambat\NepaliCalendar\Rules\NepaliDateFormatRule;
use Sambat\NepaliCalendar\Rules\NepaliDateBeforeRule;
use Sambat\NepaliCalendar\Rules\NepaliDateAfterRule;
use Sambat\NepaliCalendar\Rules\NepaliDateBetweenRule;

'date' => ['required', new NepaliDateRule],
'date' => ['required', new NepaliDateFormatRule('Y-m-d')],
'date' => ['required', new NepaliDateBeforeRule('2083-04-01')],
'date' => ['required', new NepaliDateAfterRule('2083-01-01')],
'date' => ['required', new NepaliDateBetweenRule('2083-01-01', '2083-12-30')],
```

Rules validate that the value is a *valid BS date* (respecting the real month lengths,
e.g. Falgun 2081 has 29 days) and that ranged values are in the right order.

## Eloquent casts

Store the canonical Gregorian date; present BS everywhere in your app. This keeps the
database queryable by standard date logic while the app speaks BS.

```php
use Sambat\NepaliCalendar\Casts\NepaliDateCast;
use Sambat\NepaliCalendar\Casts\NepaliDateTimeCast;

class Invoice extends Model
{
    protected $casts = [
        'bill_date'      => NepaliDateCast::class,       // date-only
        'appointment_at' => NepaliDateTimeCast::class,   // keeps the time
    ];
}
```

```php
$invoice->bill_date;                              // NepaliDate
$invoice->bill_date->format('l, F j, Y');         // सोमबार, फागुन ५, २०८१

// Assignment accepts anything parse() accepts
$invoice->bill_date = '2081-11-05';               // or NepaliDate | Carbon | array
$invoice->save();

// The column stores the AD equivalent (2025-02-17) — canonical, sortable, indexable.
```

## Query helpers

Query **AD-backed** columns with **BS predicates** — no `whereRaw`, no timezone math:

```php
Report::whereNepaliDate('bill_date', '2081-11-05')->get();
Report::whereNepaliYear('bill_date', 2081)->get();
Report::whereNepaliMonth('bill_date', 2081, 11)->get();
Report::whereNepaliDay('bill_date', 2081, 11, 5)->get();
Report::whereNepaliBetween('bill_date', '2081-05-01', '2081-07-30')->get();
Report::orderByNepaliDate('bill_date', 'desc')->get();
```

The macros are registered on `Illuminate\Database\Query\Builder` (and therefore on
Eloquent models and `DB::table()`). Values can be BS strings, `NepaliDate`, Carbon, etc.
`whereNepaliMonth`/`whereNepaliDay` also accept `NepaliDate` instances to extract
year/month/day from.

## Blade directives

```blade
@nepaliDate($user->birth_date)                      {{-- २०८१-११-०५ --}}
@nepaliDate($user->birth_date, 'l, F j, Y')         {{-- सोमबार, फागुन ५, २०८१ --}}
@nepaliDateHuman($user->birth_date)                 {{-- १ वर्ष अघि --}}
```

## Carbon macros

```php
Carbon::parse('2025-02-17')->toNepaliDate();        // NepaliDate
Carbon::parse('2025-02-17')->formatNepali('Y-m-d'); // २०८१-११-०५
```

Carbon remains **optional** — the core package only `suggests` it; the macros are
registered only when Carbon is installed (Laravel always ships it).

## Artisan commands

```bash
php artisan nepali:convert 2025-02-17                  # २०८१-११-०५
php artisan nepali:convert 2081-11-05 --from=bs --to=ad
php artisan nepali:info 2081-11-05 --language=roman     # full breakdown
php artisan nepali:info                                 # today's breakdown
php artisan nepali:seed                                 # populate the DB driver's table
php artisan nepali:seed --fresh                         # replace existing rows
php artisan nepali:seed --connection=mysql              # seed a specific connection
```

`nepali:info` prints the active **Data source** (algorithm vs database) so you can verify
which driver is serving conversions.

## Global helpers

Available everywhere (plain PHP included):

```php
nepali_date('2081-11-05')      // NepaliDate object (or today when called bare)
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
bs_fiscal_year('2083-08-15')   // NepaliFiscalYear
bs_date_range($from, $to)      // inclusive NepaliDateRange
```

## Publishable assets

```bash
php artisan vendor:publish --tag=nepali-calendar-config      # config/nepali-calendar.php
php artisan vendor:publish --tag=nepali-calendar-migrations  # DB driver migrations
```

## Plain PHP (no Laravel)

Every feature on the [Dates](05-dates.md), [Conversion](03-conversion.md),
[Formatting](04-formatting.md), [Ranges](06-date-ranges.md), [Fiscal year](07-fiscal-year.md)
and [Business days](08-business-days.md) pages works without Laravel. The `Support\Config`
class reads `config()` when the Laravel helper exists and falls back to defaults otherwise.
The `database` driver and the query/cast/Blade/artisan features are Laravel-only by
nature — the rest is framework-agnostic.
