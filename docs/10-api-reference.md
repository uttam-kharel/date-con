# API reference

Compact reference of every public class and method. All examples assume
`use Sambat\NepaliCalendar\…` imports.

---

## `NepaliDate` — the immutable BS date value object

### Construction

| Method | Description |
|---|---|
| `NepaliDate::now()` | current moment in BS |
| `NepaliDate::today()` | today in BS (no time) |
| `NepaliDate::fromBs(int $y, int $m, int $d, ?CarbonInterface $time = null)` | from BS parts |
| `NepaliDate::fromAd(mixed $value)` | from any AD value (Carbon, string, timestamp) |
| `NepaliDate::parse(mixed $value)` | flexible parse — see [conversion](03-conversion.md) |
| `NepaliDate::fromTimestamp(int $ts)` | from unix timestamp |
| `NepaliDate::fromCarbon(CarbonInterface $c)` | from Carbon |
| `NepaliDate::fromArray(array $value)` | `['year'=>…,'month'=>…,'day'=>…]` or `[y,m,d]` |
| `NepaliDate::isValid(int $y, int $m, int $d)` | static validity check |
| `NepaliDate::assertValidBsDate(int $y, int $m, int $d)` | throws on invalid |

### Getters

`year()` · `month()` · `day()` · `weekDay()` · `weekDayIso()` · `dayOfYear()` ·
`daysInMonth()` · `daysInYear()` · `isLeapYear()` · `monthName(?lang)` ·
`monthShortName(?lang)` · `weekDayName(?lang)` · `weekDayShort(?lang)` ·
`ad()` · `timestamp()` · `age()`

### Formatting

`format(string $f, ?string $lang = null, ?bool $devanagari = null)` ·
`formatPreset(DateFormat $p, ?string $lang = null, ?bool $devanagari = null)` ·
`formatAd(string $f)` · `formatBoth()` · `toDateString($sep = '-')` ·
`toNepaliNumerals()` · `toEnglishNumerals()` · `toArray()` · `toJson()` ·
`jsonSerialize()` · `__toString()`

### Arithmetic (all return new instances)

`addDay/subDay` · `addDays/subDays` · `addWeeks/subWeeks` · `addMonths/subMonths` ·
`addYears/subYears` · `nextDay/previousDay` · `nextMonth/previousMonth` ·
`nextYear/previousYear` · `nextWeekday/previousWeekday` · `nextBusinessDay/previousBusinessDay`

### Periods

`startOfMonth/endOfMonth` · `firstOfMonth/lastOfMonth` · `startOfYear/endOfYear` ·
`startOfQuarter/endOfQuarter` · `startOfWeek(?$startsOn)/endOfWeek(?$startsOn)` ·
`startOfFiscalYear/endOfFiscalYear` · `calendar(?string $weekStartsOn)` ·
`withYear(int)` · `withMonth(int)` · `withDay(int)` · `copy()`

### Comparison

`isBefore` · `isAfter` · `isBetween($a, $b)` · `isSameDay` · `isSameMonth` ·
`isSameYear` · `equals(mixed)` · `compareTo(mixed)` · `isToday` · `isTomorrow` ·
`isYesterday` · `isPast` · `isFuture` · `isWeekday` · `isWeekend` · `isHoliday` ·
`isBusinessDay` · `isWorkingDay`

### Navigation & diffs

`tomorrow()` · `yesterday()` · `addBusinessDays(int)` · `subBusinessDays(int)` ·
`businessDaysUntil(mixed)` · `diffInDays/months/years/weeks/hours/minutes/seconds`
(`(mixed $other, bool $absolute = true)`) · `diffForHumans(?lang)` ·
`fiscalYear()` · `fiscalQuarter()` · `rangeTo(mixed $end)`

---

## `NepaliDateRange` — inclusive ranges

| Method | Description |
|---|---|
| `NepaliDateRange::between(mixed $start, mixed $end)` | create an inclusive range |
| `NepaliDateRange::fromDates(NepaliDate $s, NepaliDate $e)` | create from date objects |
| `start()` / `end()` | boundary `NepaliDate`s |
| `count()` / `isEmpty()` | size (inclusive) |
| `contains(mixed)` / `containsRange(self)` | membership |
| `overlaps(self)` / `touches(self)` | relationship tests |
| `merge(self)` / `intersection(self)` / `gap(self)` | set ops — `?self` (null when empty) |
| `days()` / `weeks(?$startsOn)` / `months()` / `years()` / `daysEvery(int)` | slicing |
| `businessDays()` / `workingDays()` / `weekends()` / `holidays()` | working-day lists |
| `businessDayCount()` | int |
| `toDateStrings($sep = '-')` / `toArray()` / `toJson()` / `__toString()` | serialization |
| `toCsv($sep = ',', $header = true)` / `toIcs(?$title, $productId)` | export |
| `getIterator()` | `foreach` over days |

---

## `Recurrence` — rule-based date lists

| Method | Description |
|---|---|
| `Recurrence::daily(?$start)` / `weekly(?$start)` / `monthly(?$start)` / `yearly(?$start)` | factories |
| `from(mixed $start)` | set start |
| `every(int $interval)` | step interval |
| `on(...$weekdays)` | weekly weekday filter (names or numbers) |
| `until(mixed $end)` | inclusive end |
| `between(mixed $s, mixed $e)` | window |
| `take(int $limit)` | occurrence cap |
| `frequency()` / `interval()` / `start()` / `end()` | introspection |
| `dates()` / `count()` / `getIterator()` | consumption |
| `toDateStrings($sep = '-')` / `toArray()` / `toJson()` | serialization |

Constant: `Recurrence::MAX_OCCURRENCES = 10000`.

---

## `NepaliFiscalYear` — Shrawan-based fiscal years

| Method | Description |
|---|---|
| `NepaliFiscalYear::forYear(int $year)` | e.g. 2083 → 2083/84 |
| `NepaliFiscalYear::fromDate(mixed $date)` | fiscal year containing a date |
| `year()` / `label()` | `2083` / `'2083/84'` |
| `startDate()` / `endDate()` | Shrawan 1 … Ashadh end (next year) |
| `days()` | 365/366 |
| `contains(mixed)` | membership |
| `quarter(?NepaliDate $d = null)` | 1–4 |
| `quarters()` / `quarterRange(int $q)` | ranges |
| `isCurrent()` | today inside? |
| `toArray()` / `toJson()` | serialization |

Constant: `NepaliFiscalYear::START_MONTH = 4` (Shrawan).

---

## Enums

### `Enums\DateFormat`

`SHORT` (`Y-m-d`) · `MEDIUM` (`Y M d`) · `LONG` (`F j, Y`) · `FULL` (`l, F j, Y`) ·
`DATETIME_SHORT` · `DATETIME_MEDIUM` · `DATETIME_LONG` · `DATETIME_FULL` ·
`TIME_SHORT` (`H:i`) · `TIME_MEDIUM` (`H:i:s`) · `TIME_FULL` (`g:i:s A`)

### `Enums\CalendarLanguage`

`Nepali` (`'nepali'`) · `Roman` (`'roman'`) · `English` (`'english'`)

### `Enums\NepaliMonth`

`Baisakh = 1` … `Chaitra = 12` (Baisakh, Jestha, Ashadh, Shrawan, Bhadra, Ashwin,
Kartik, Mangsir, Poush, Magh, Falgun, Chaitra)

---

## Holidays

| Class | Key methods |
|---|---|
| `Holidays\NepaliHoliday` | `fromConfig(mixed, ?$name)` · `date()` · `name()` · `type()` · `toArray()` |
| `Holidays\HolidayCollection` | `__construct(iterable)` · `all()` · `forYear(int)` · `contains(NepaliDate)` · `forDate(NepaliDate)` · `names()` · `dates()` · `isEmpty()` · `count()` · `getIterator()` · `toArray()` · `toJson()` |
| `Holidays\HolidayRepository` | `instance()` · `setInstance(?self)` · `fromConfig()` · `fromArray(array)` · `all()` · `forYear(int)` · `contains(NepaliDate)` · `forDate(NepaliDate)` |

---

## Exceptions

| Exception | Extends | Thrown for |
|---|---|---|
| `InvalidNepaliDateException` | `InvalidArgumentException` | any invalid BS date |
| `NepaliDateOutOfRangeException` | `InvalidNepaliDateException` | outside BS 2000–2100 |

---

## Contracts & providers

| Class | Purpose |
|---|---|
| `Contracts\CalendarDataProvider` | implement to supply calendar data from anywhere |
| `Providers\AlgorithmCalendarDataProvider` | built-in table (default) |
| `Providers\DatabaseCalendarDataProvider` | reads the seeded `nepali_calendar_years` table |
| `Calendar` | conversion engine — `setProvider()` / `resetProvider()` / static conversions |

---

## Laravel surface

| Class / rule | Purpose |
|---|---|
| `Rules\NepaliDateRule` | valid BS date |
| `Rules\NepaliDateFormatRule($format)` | valid BS date matching a format |
| `Rules\NepaliDateBeforeRule($date)` / `NepaliDateAfterRule($date)` / `NepaliDateBetweenRule($a, $b)` | ranged validity |
| `Casts\NepaliDateCast` / `Casts\NepaliDateTimeCast` | Eloquent casts (AD storage, BS presentation) |
| `Query\NepaliDateQueryBuilder` | `whereNepaliDate/Year/Month/Day/Between`, `orderByNepaliDate` macros |
| `Facades\NepaliDate` | facade over the class API |
| `NepaliCalendarServiceProvider` | registration, config, commands, macros |

Blade directives: `@nepaliDate($value, $format = null)` · `@nepaliDateHuman($value)`.

Carbon macros: `toNepaliDate()` · `formatNepali($format)`.

Artisan: `nepali:convert` · `nepali:info` · `nepali:seed [--fresh] [--connection=]`.
