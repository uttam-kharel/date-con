# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Changed

- **Release hardening** — `bin/check-release.php` verifies a tag's semantic version,
  the `VERSION` constant and the CHANGELOG entry before a release is created; the
  `tests` workflow now also runs the full Laravel matrix against every `v*` tag push;
  the `release` workflow gained a manual `workflow_dispatch` trigger.
  `RELEASING.md` is now the complete release handbook (rules, checklist, CI matrix,
  tag history, backfill, rollback, dataset governance).

## [1.14.0] - 2026-08-14

### Added

- **`NepaliDate::createFromFormat()`** — the reverse of `format()`. Parses a BS
  date from a format string with `Y/y`, `m/n`, `d/j`, `F/M` tokens; month names
  accept Devanagari (`साउन`) and romanized (`Shrawan` / `Shr`) forms; Devanagari
  numerals are transparent; other characters are matched literally (`\\` escapes).
- **Period comparisons** — `isSameWeek()`, `isSameQuarter()` and
  `isSameFiscalYear()` alongside the existing `isSameDay()` / `isSameMonth()` /
  `isSameYear()`.
- 17 new tests (**198 tests, 2,873 assertions** in total).

## [1.13.0] - 2026-08-14

### Added

- **Relative date parsing** — `NepaliDate::parse()` now accepts Nepali words
  (`आज`, `हिजो`, `अस्ति`, `भोलि`, `पर्सि`, `परसि`) and English words
  (`today`, `tomorrow`, `yesterday`, `next/last week|month|year`), with
  month-end clamping via the existing BS arithmetic.
- **Currency helpers on `NepaliNumber`** — `formatCurrency()`
  (`रु. १,२५,०००.५०` / `Rs. 1,25,000.50`) and `currencyInWords()`
  (`रुपैयाँ एक लाख पच्चीस हजार पचास पैसा मात्र` / `Rupees … and fifty paise only`)
  for cheques, receipts and invoices.
- 5 new tests (**181 tests, 2,845 assertions** in total).

## [1.12.0] - 2026-08-14

### Added

- **`NepaliNumber`** — the public numeral engine: `toNepali()` / `toEnglish()`
  digit conversion, `format()` with Indian (lakh/crore) grouping
  (`१,२५,०००`), and **number-to-words** in English (`one lakh twenty-five
  thousand`) and Nepali (`एक लाख पच्चीस हजार`) for invoices, receipts and
  cheques. Supports 0 – 99,99,99,999 with the full irregular Nepali compound
  table (21–99), negatives and the `nepali_number_words()` helper.
- 9 new tests (**176 tests, 2,818 assertions** in total).

## [1.11.0] - 2026-08-14

### Added

- **Nepali culture** — `NepaliDate::season()` (`Season` enum, six ऋतु with
  Devanagari / Roman / English names) and `NepaliDate::rashi()` (`Rashi` enum,
  twelve zodiac signs by BS month), plus `bs_season()` / `bs_rashi()` helpers and
  Season / Rashi rows in `nepali:info`.
- **Age & birthdays** — `ageAt()`, `isBirthday()` (month-end days celebrated on the
  last existing day), `nextBirthday()` (clamped, strictly after today).
- **Broken-down diffs** — `diffInYearsMonthsDays()` and `ageInYearsMonthsDays()`
  return `[years, months, days]` using BS-native month-end clamping.
- `InvalidNepaliMonthException` for out-of-range month numbers.
- 16 new tests (**167 tests, 2,772 assertions** in total).

## [1.10.1] - 2026-08-14

### Fixed

- **Provider no longer crashes on Laravel 10** — `publishesMigrations()` only exists
  on Laravel 11+; the provider now falls back to the classic `publishes()` call so
  the package boots on every supported version (10 – 13).
- **DB-backed feature tests run on in-memory SQLite** — testbench 8 (Laravel 10)
  defaults to MySQL, which is not available in CI; `phpunit.xml` now pins
  `DB_CONNECTION=sqlite` + `DB_DATABASE=:memory:` so the suite is environment-agnostic.
- **CI install step fixed** — the Laravel matrix is pinned via
  `composer require --no-update` (a plain `composer update` then resolves), because
  `composer update pkg:constraint` is not allowed without a lock file.
- **CI matrix corrected to what is actually exercisable in 2026** — Laravel 11 – 13 on
  PHP 8.2 – 8.5. PHP 8.1 and Laravel 10 stay supported in `composer.json`, but they are
  EOL and their dependency toolchains are blocked by security advisories (Laravel 10
  stable releases, and pest ≤ 2.36.0 against the newer phpunit security releases), so
  no pest-based stack resolves for them on a modern runner.

Verified against **Laravel 10 (testbench 8), 12 (testbench 10) and 13 (testbench 11)** —
**151 tests / 2,713 assertions**.

## [1.10.0] - 2026-08-14

### Added

- **Laravel 13 support** — `illuminate/contracts` and `illuminate/support` now
  accept `^13.0`, so the package installs and runs on the current Laravel release
  line with no code changes (the provider, facade, casts, validation rules and
  query macros use only stable framework APIs).
- **Full Laravel test matrix in CI** — every supported major (10, 11, 12, 13) is
  now exercised explicitly across the PHP lines it supports, with the newest
  Laravel 13.x verified on PHP 8.4 / 8.5 (the dev toolchain upgraded to
  testbench 11 and Pest 4/5).

### Changed

- Dev dependencies widened so the suite can run on any supported combination:
  `orchestra/testbench` `^8.0 – ^11.0`, `pestphp/pest` `^2.0 – ^5.0`,
  `pestphp/pest-plugin-laravel` `^2.4 – ^5.0`.
- Version support docs (README, installation and Laravel guides) now state
  **Laravel 10 – 13**.

No API changes — this release is a compatibility and tooling update (**151 tests,
2,713 assertions** verified against Laravel 13.25).

## [1.9.0] - 2026-08-14

### Added

- **`Recurrence` engine** — fluent daily / weekly / monthly / yearly rules with
  `every()`, weekly `on('monday', 'friday')`, `between()` / `until()` and
  `take()`, iterable and serializable. Materialization is guarded: a rule can
  never produce more than 10,000 occurrences, so unbounded rules fail loudly
  instead of looping forever.
- **Range export** — `NepaliDateRange::toCsv()` (bs_date, ad_date, Nepali and
  English weekday columns) and `NepaliDateRange::toIcs()` (RFC 5545 VEVENT with
  an exclusive end date) for reports, spreadsheets and calendar apps.
- 9 new tests (**151 tests, 2,713 assertions** in total).

## [1.8.0] - 2026-08-14

### Added

- **Range operations** on `NepaliDateRange` — `containsRange()`, `overlaps()`,
  `touches()`, `merge()`, `intersection()` and `gap()` for scheduling and
  reporting; `daysEvery($step)` for sampling; and business-day counting with
  `businessDays()` / `workingDays()`, `businessDayCount()`, `weekends()` and
  `holidays()`.
- **Day-level helpers on `NepaliDate`** — `tomorrow()`, `yesterday()`,
  `isTomorrow()`, `isYesterday()`, `isWeekday()`, `nextWeekday()`,
  `previousWeekday()`.
- **Time diffs** — `diffInSeconds()`, `diffInMinutes()` and `diffInHours()`
  between the AD instants (signed or absolute).
- **`formatBoth()`** — the same instant rendered in both calendars: Nepali
  names with Devanagari numerals and the Gregorian date in English.
- 9 new tests (**142 tests, 2,681 assertions** in total).

## [1.7.0] - 2026-08-14

### Added

- **Calendar range extended to BS 2100** (AD 1943-04-14 .. 2044-04-12). BS 2100 is
  the community-verified continuation (Nepal Panchanga Nirnayak Samiti based),
  cross-checked against independent Nepali calendar sites which place Baisakh 1
  2100 on April 14 2043 — one day after the previously verified 2099-12-30 =
  2043-04-13 boundary. Historical years 2000-2099 were not touched.
- Out-of-range exception messages now derive their range labels from the active
  dataset instead of hardcoding them.
- GitHub Actions **release workflow** (`release.yml`) that runs the suite on
  every `v*` tag and creates a GitHub Release, plus a **`RELEASING.md`** guide
  documenting the full release process.
- 5 new tests covering the extended boundary, the BS 2100 anchors, the new range
  and the 101-year seed (**133 tests, 2,633 assertions** in total).

## [1.6.0] - 2026-08-14

### Added

- **Format presets** — the `DateFormat` enum (`SHORT`, `MEDIUM`, `LONG`, `FULL`,
  `DATETIME_SHORT/MEDIUM/LONG/FULL`, `TIME_SHORT/MEDIUM/FULL`) with
  `NepaliDate::formatPreset(DateFormat::FULL)`, which render through the normal
  format engine (Devanagari numerals and Nepali names by default):
  `फागुन ५, २०८१`, `सोमबार, फागुन ५, २०८१`.
- 2 new tests covering presets in all three name languages and the time
  variants (**128 tests, 2,604 assertions** in total).

## [1.5.0] - 2026-08-14

### Added

- **Ranged validation rules** — `nepali_date_before:date`, `nepali_date_after:date`
  and `nepali_date_between:start,end` string rules plus the object-style
  `NepaliDateBeforeRule`, `NepaliDateAfterRule` and `NepaliDateBetweenRule`.
- **Eloquent casts** — `NepaliDateCast` (date-only) and `NepaliDateTimeCast`
  (preserves the time of day). The canonical Gregorian value is stored in the
  database; the model presents the BS date on read.
- **Query helpers** — `whereNepaliDate`, `whereNepaliYear`, `whereNepaliMonth`,
  `whereNepaliDay`, `whereNepaliBetween` and `orderByNepaliDate` registered as
  macros on Eloquent's Builder, translating BS predicates onto canonical
  AD-backed columns.
- 12 new tests covering the ranged rules, casts and query helpers
  (**126 tests, 2,593 assertions** in total).

### Fixed

- Custom validation rule messages now interpolate the actual attribute name
  (Laravel does not substitute `:attribute` inside replacer output).

## [1.4.0] - 2026-08-14

### Added

- **Holidays** — `NepaliHoliday` value object, `HolidayCollection` and a
  config-driven `HolidayRepository`. Holidays are supplied through the
  `holidays` config array (date string, `'date' => 'name'` pair, or
  `['date' => ..., 'name' => ..., 'type' => ...]`), a container binding
  (`nepali-calendar.holidays`), or `HolidayRepository::setInstance()` in
  tests. The core never hardcodes festive dates.
- **Business days on `NepaliDate`** — `isBusinessDay()` / `isWorkingDay()`,
  `addBusinessDays()` / `subBusinessDays()`, `nextBusinessDay()` /
  `previousBusinessDay()` and signed `businessDaysUntil()`.
- **Configurable weekend** — the `weekend` config option (default `[6]` =
  Saturday, PHP weekday numbers) drives `isWeekend()` and all business-day
  arithmetic; `isWeekend()` is no longer hardcoded to Saturday.
- 9 new tests covering weekends, business-day arithmetic, holiday parsing,
  serialization and config-driven behavior (**114 tests, 2,559 assertions**).

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
