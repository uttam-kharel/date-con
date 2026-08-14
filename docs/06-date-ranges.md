# Date ranges, recurrence & export

## `NepaliDateRange` — inclusive BS ranges

```php
use Sambat\NepaliCalendar\NepaliDateRange;

$range = NepaliDateRange::between('2081-05-01', '2081-07-30');   // inclusive both ends
$range = NepaliDateRange::fromDates($startNepaliDate, $endNepaliDate);
```

Anything `NepaliDate::parse()` accepts works as a boundary (strings, Carbon, arrays).

### Reading a range

```php
$range->start();           // NepaliDate 2081-05-01
$range->end();             // NepaliDate 2081-07-30
$range->count();           // 91 (inclusive)
$range->isEmpty();         // false
$range->contains('2081-06-15');   // true
$range->containsRange($other);    // true if $other fits entirely inside

foreach ($range as $day) { /* NepaliDate, iterable */ }
$range->days();            // list<NepaliDate>
$range->toDateStrings();   // ['2081-05-01', …, '2081-07-30']
```

### Slicing

```php
$range->weeks();           // chunks by week (config week_starts_on)
$range->weeks('monday');   // or explicit first day
$range->months();          // one sub-range per month
$range->years();           // one sub-range per year
$range->daysEvery(7);      // every 7th day of the range
```

### Set operations

```php
$a = NepaliDateRange::between('2083-01-10', '2083-01-15');
$b = NepaliDateRange::between('2083-01-01', '2083-01-31');  // Baisakh 2083, 31 days

$a->overlaps($b);          // true — any day in common
$a->touches($b);           // true if they meet without overlapping (end + 1 = start)
$a->merge($b);             // NepaliDateRange 2083-01-01..2083-01-31, or null if disjoint
$a->intersection($b);      // 2083-01-10..2083-01-15, or null
$b->gap($other);           // NepaliDateRange of days between them, or null
```

`merge()`/`intersection()`/`gap()` return `null` when the result would be empty — check
before using.

### Business-day counting

```php
$range->businessDays();        // list<NepaliDate> skipping weekends + holidays
$range->workingDays();         // alias of businessDays()
$range->businessDayCount();    // int
$range->weekends();            // list<NepaliDate>
$range->holidays();            // list<NepaliDate>
```

Weekends and holidays come from config (`weekend`, `holidays`) — see
[Business days & holidays](08-business-days.md).

### Serialization

```php
$range->toArray();         // ['start' => …, 'end' => …, 'count' => …]
$range->toJson();          // JSON
$range->__toString();      // '2081-05-01 .. 2081-07-30'
```

## Recurrence

`Recurrence` builds rule-based date lists on top of ranges. Rules are **guarded**: an
unbounded rule throws instead of looping forever.

```php
use Sambat\NepaliCalendar\Recurrence;

// Daily
Recurrence::daily('2083-01-01')->take(10);          // first 10 days
Recurrence::daily()->until('2083-01-31');           // defaults start = today

// Weekly — optionally on specific weekdays
Recurrence::weekly('2083-01-01')
    ->on('monday', 'friday')                        // names or numbers 1–7 / 0–6
    ->between('2083-01-01', '2083-12-30');

// Monthly — every Nth month, on a fixed day
Recurrence::monthly('2083-01-05')->every(2)->until('2083-12-30');

// Yearly
Recurrence::yearly('2083-04-01')->take(3);
```

### The builder

| Method | Purpose |
|---|---|
| `daily() / weekly() / monthly() / yearly()` | start the rule; optional start date |
| `from($date)` | set / change the start date |
| `every(int $interval)` | every Nth day / week / month / year |
| `on(...$weekdays)` | weekly only — e.g. `on('monday')`, `on(1, 5)` |
| `until($date)` | stop at this date (inclusive) |
| `between($start, $end)` | constrain to a window |
| `take(int $limit)` | cap the number of occurrences |

### Consuming

```php
$rule = Recurrence::monthly('2083-01-05')->every(2)->until('2083-12-30');

$rule->dates();             // list<NepaliDate>
$rule->count();             // int
$rule->frequency();         // 'monthly'
$rule->interval();          // 2
$rule->start();             // ?NepaliDate
$rule->end();               // ?NepaliDate
$rule->toDateStrings();     // ['2083-01-05', '2083-03-05', …]
$rule->toArray(); $rule->toJson();

foreach ($rule as $date) { /* iterable */ }
```

### Guardrails

A rule must be bounded by `until`, `between`, `take`, or the dataset's end — otherwise
`dates()` throws. `Recurrence::MAX_OCCURRENCES` (10,000) is the hard cap:

```php
Recurrence::daily('2083-01-01')->dates();   // throws — unbounded, would exceed 10,000
Recurrence::daily('2083-01-01')->take(10)->dates();  // fine
```

## Export

Any range (or the result of a recurrence) exports to CSV and iCalendar:

```php
// RFC-4180 CSV with header: bs_date,ad_date,weekday_nepali,weekday_english
$csv = $range->toCsv();                    // string
$range->toCsv(';');                        // custom separator
$range->toCsv(',', false);                 // no header row

// RFC-5545 iCalendar — one VEVENT per day
$ics = $range->toIcs('Ward schedule');     // string
$range->toIcs('Ward schedule', '-//MyOrg//Calendar//EN');  // custom product id
```

Save them straight to a file or response:

```php
Storage::put('schedule.csv', $range->toCsv());
return response($range->toIcs('Bill due dates'))
    ->header('Content-Type', 'text/calendar');
```
