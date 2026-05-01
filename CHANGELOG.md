# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.3.0] - 2026-08-14

### Added

- **`NepaliDateRange`** — immutable, inclusive BS date ranges:
  `NepaliDateRange::between('2081-11-05', '2081-12-31')`, day-by-day iteration,
  `count()`, `contains()`, `days()`, and slicing into calendar weeks, BS months
  and BS years (`weeks()`, `months()`, `years()`). Reversed bounds are normalized.
- **`NepaliFiscalYear`** — the Government of Nepal fiscal year (Shrawan 1 of BS N
  through Ashadh 31 of BS N+1): `fromDate()`, `forYear()`, `label()` (`2083/84`),
  `startDate()` / `endDate()`, `days()`, `contains()`, `quarter()`, `quarters()`
  and `quarterRange()`.
- **Fiscal helpers on `NepaliDate`** — `fiscalYear()`, `fiscalQuarter()` (1-4,
  Shrawan-based), `startOfFiscalYear()`, `endOfFiscalYear()` and `rangeTo()`.
- **Global helpers** — `bs_fiscal_year()` and `bs_date_range()`.
- 17 new tests covering ranges, fiscal years and fiscal quarters
  (**105 tests, 2,510 assertions** in total).

### Fixed

- Fiscal-year math follows the real convention: the year runs to Ashadh of the
  next BS year, and labels use the short form (`2083/84`, not `2083/2084`).

## [1.2.0] - 2026-08-14

### Added

- Explicit **PHP 8.1, 8.2, 8.3, 8.4 and 8.5** support, verified by a GitHub Actions
  matrix that runs the full test suite on every supported PHP version (plus a
  `--prefer-lowest` dependency run on PHP 8.1).
- `LICENSE.md`, `CONTRIBUTING.md`, `SECURITY.md` and this changelog.
- `.gitattributes` so Composer distributions stay lean (tests, CI and docs excluded).

### Changed

- `composer.json`: the PHP constraint now lists every supported version explicitly,
  authors are recorded, and `illuminate/database` is suggested for the database driver.
- `README.md`: version badges and a contributing section.

## [1.1.0] - 2026-08-14

### Added

- **Configurable data source.** A new `driver` config option
  (`algorithm` | `database`, default `algorithm`) lets applications keep the BS
  month-length table in their own database instead of shipping it in the package.
- `CalendarDataProvider` contract with two shipped implementations:
  `ArrayCalendarDataProvider` (built-in constants) and `DatabaseCalendarDataProvider`
  (Laravel connection or plain PDO). Bind your own implementation as
  `nepali-calendar.provider` for fully custom sources.
- Publishable `nepali_calendar_years` migration and a `nepali:seed` artisan command
  (with `--fresh` and `--connection` options) that populate the table from the
  verified constant data - ~100 rows, not the 52,816 per-date rows other packages use.
- `Calendar::setProvider()` / `Calendar::resetProvider()` for runtime and test swaps.
- The active data source is now shown by `php artisan about` and `nepali:info`.
- 7 new tests covering driver resolution, DB/algorithm conversion parity, seeding
  guardrails, empty/partial-table errors, custom container providers and plain-PHP
  PDO usage (**88 tests, 2,429 assertions** in total).

## [1.0.0] - 2026-08-14

### Added

- AD ⇄ BS conversion engine over **BS 2000-2099 / AD 1943-2043**, using precomputed
  cumulative day counts (O(1)) instead of day-by-day loops, anchored at
  BS 2000-01-01 = AD 1943-04-14 and verified against independently known dates.
- Immutable, Carbon-style `NepaliDate` value object: flexible parsing (separators,
  compact, Devanagari digits, month names, arrays, Carbon, timestamps), full PHP
  `date()` formatting with Devanagari numerals and Nepali / Romanized / English
  names, arithmetic (days, weeks, months with clamping, years), diffs, age,
  `diffForHumans`, periods, calendar grids, comparisons and serialization.
- Laravel integration: service provider, facade, factory, `nepali_date` and
  `nepali_date_format` validation rules, `@nepaliDate` / `@nepaliDateHuman` Blade
  directives, Carbon macros, `nepali:convert` / `nepali:info` artisan commands and
  global helpers. Works in plain PHP with no database.
- Pest test suite (**81 tests, 2,400+ assertions**) and this README.
