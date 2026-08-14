# Introduction

**sambat/nepali-calendar** is a complete Bikram Sambat (BS / Nepali) date & time package
for PHP 8.1 – 8.5. It works in plain PHP **and** Laravel 10 – 12, with Laravel support
layered on top rather than required.

It was written after a detailed study of the two existing Nepali date packages
(`hmis/nepali-date` and `mr.incognito/date-converter`) and fixes every gap found in them:
no mandatory database, O(1) conversions, typed value objects, real validation, formatting,
arithmetic, localization, and 151 tests with 2,700+ assertions.

## Feature inventory

| Area | What you get |
|---|---|
| **Conversion** | AD ⇄ BS, O(1), verified anchors, range BS 2000–2100 ⇄ AD 1943–2044 |
| **Parsing** | `Y-m-d`, `/`, compact, Devanagari digits, month names, arrays, timestamps, Carbon |
| **Date object** | Immutable `NepaliDate` value object with full getters, arithmetic, comparison |
| **Formatting** | Full PHP `date()` token set + escapes, Devanagari numerals, 3 languages, named presets |
| **Localization** | Nepali (Devanagari), Romanized, English names; `formatBoth()` bilingual output |
| **Arithmetic** | `addDays`/`subMonths`/`addYears`/… with month-length clamping (all immutable) |
| **Periods** | `startOf*`/`endOf*`, calendar grids, first/last of month, `with*` copies |
| **Diffs** | Days/months/years/weeks/hours/minutes/seconds, `diffForHumans()`, `age()` |
| **Ranges** | `NepaliDateRange`: iteration, slicing, overlap/merge/intersection/gap, export |
| **Fiscal year** | Shrawan-based `NepaliFiscalYear` (`2083/84`), quarters, quarter ranges |
| **Business days** | Configurable weekends + holidays, `addBusinessDays()`, counting on ranges |
| **Holidays** | `NepaliHoliday` / `HolidayCollection` / `HolidayRepository` — never hardcoded |
| **Recurrence** | Daily/weekly/monthly/yearly rules with `every`/`on`/`until`/`take` + guardrails |
| **Export** | RFC-4180 CSV and RFC-5545 iCalendar (.ics) from any range |
| **Laravel** | Validation rules, Eloquent casts, query helpers, Blade, Carbon macros, artisan, facade |
| **Data source** | Built-in algorithm (no DB) **or** your own database table **or** any custom provider |
| **Errors** | Typed exceptions — never `null`/`false`/error strings from successful-looking methods |

## Package layout

```
src/
├── NepaliDate.php              The core immutable date value object
├── NepaliDateFactory.php       Named constructors (now/today/parse/fromBs/fromAd/…)
├── NepaliDateRange.php         Inclusive date ranges + set operations + export
├── NepaliFiscalYear.php        Shrawan-based fiscal years & quarters
├── Recurrence.php              Fluent recurrence rules with guardrails
├── Calendar.php                Conversion engine (O(1)), provider resolution
├── CalendarDataProvider.php*   Where month data comes from (see contracts/)
├── Contracts/                  CalendarDataProvider contract for custom data sources
├── Casts/                      Eloquent casts (NepaliDateCast, NepaliDateTimeCast)
├── Commands/                   artisan nepali:convert / nepali:info / nepali:seed
├── Enums/                      DateFormat, CalendarLanguage, NepaliMonth
├── Exceptions/                 InvalidNepaliDateException, NepaliDateOutOfRangeException
├── Facades/                    NepaliDate facade
├── Holidays/                   NepaliHoliday, HolidayCollection, HolidayRepository
├── Providers/                  algorithm + database data providers
├── Query/                      NepaliDateQueryBuilder (where/orderBy macros)
├── Rules/                      5 validation rule classes
└── Support/                    Config fallbacks, Formatter, Blade helpers
```

## Version support

| Version | PHP | Status |
|---|---|---|
| 1.x (current) | ^8.1 – ^8.5 | ✅ current, 13 tagged releases |
| 2.x (planned) | ^8.2+ | roadmap |
| 3.x (planned) | ^8.3+ | roadmap |

See [ROADMAP.md](../ROADMAP.md) for the full version-by-version plan.

## The 30-second tour

```php
use Sambat\NepaliCalendar\NepaliDate;

$date = NepaliDate::fromAd('2025-02-17');     // AD → BS
echo $date;                                   // २०८१-११-०५

echo $date->format('l, F j, Y');              // सोमबार, फागुन ५, २०८१
echo $date->format('l, F j, Y', 'english');   // Monday, February 17, 2025
echo $date->addDays(10)->fiscalYear()->label(); // 2081/82
```

Next: [Installation](01-installation.md) → [Quick start](02-quick-start.md).
