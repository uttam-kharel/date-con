# 📅 Nepali Calendar (sambat/nepali-calendar)

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
- ✅ **No database required** — works anywhere, even outside Laravel
- ✅ 81 tests / 2400+ assertions, O(1) conversions

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
| Artisan commands | `nepali-date:update` (needs DB) | ✗ | `nepali:convert`, `nepali:info` |
| Parsing flexibility | exact `YYYY-MM-DD` only | `str_replace`-based, brittle | many formats + month names + Devanagari input |
| Database required | **yes** (migration + seed) | no | **no** |
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
    'language'       => 'nepali',    // nepali | roman | english
    'numerals'       => 'devanagari',// devanagari | english
    'week_starts_on' => 'sunday',    // sunday | monday
    'default_format' => 'Y-m-d',
];
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

// or rule classes
use Sambat\NepaliCalendar\Rules\NepaliDateRule;
use Sambat\NepaliCalendar\Rules\NepaliDateFormatRule;

'date' => ['required', new NepaliDateRule],
'date' => ['required', new NepaliDateFormatRule('Y-m-d')],
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
composer test        # 81 tests, 2400+ assertions
composer pint        # Laravel code style
```

## Credits & license

Inspired by (and written after studying) `hmis/nepali-date` and `mr.incognito/date-converter`,
whose shared month table this package builds upon. MIT licensed.
