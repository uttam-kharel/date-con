# Dates — the `NepaliDate` API

`NepaliDate` is an **immutable** value object — every mutation returns a new instance
and the original never changes.

## Getters

```php
$d = NepaliDate::parse('2081-11-05');

$d->year();            // 2081
$d->month();           // 11   (1 = Baisakh … 12 = Chaitra)
$d->day();             // 5
$d->weekDay();         // 1 (Sunday) … 7 (Saturday) — Nepali convention
$d->weekDayIso();      // ISO 1 (Monday) … 7 (Sunday)
$d->dayOfYear();       // 0-based day of the BS year
$d->daysInMonth();     // 29
$d->daysInYear();      // 366
$d->isLeapYear();      // true
$d->monthName();       // फागुन
$d->monthShortName();  // फागु
$d->weekDayName();     // सोमबार
$d->weekDayShort();    // सोम
$d->ad();              // Carbon of the same instant
$d->timestamp();       // unix timestamp
```

`monthName()`/`weekDayName()` accept an optional language:
`$d->monthName('english')` → `February`.

## Named constructors

```php
NepaliDate::today();                    // today in BS
NepaliDate::now();                      // this moment
NepaliDate::fromBs(2081, 11, 5);
NepaliDate::fromAd('2025-02-17');
NepaliDate::parse('2081-11-05');        // any format — see conversion docs
NepaliDate::fromTimestamp(1740000000);
NepaliDate::fromCarbon($carbon);
NepaliDate::fromArray(['year' => 2081, 'month' => 11, 'day' => 5]);
```

## Arithmetic (immutable)

```php
$d->addDay();          $d->subDay();          // +/− 1 day
$d->addDays(10);       $d->subDays(10);
$d->addWeeks(3);       $d->subWeeks(3);
$d->addMonths(2);      $d->subMonths(2);
$d->addYears(1);       $d->subYears(1);

$d->nextDay();         $d->previousDay();
$d->nextMonth();       $d->previousMonth();
$d->nextYear();        $d->previousYear();
```

**Clamping:** month arithmetic clamps to the target month's length — `2081-11-29` plus one
month lands on `2081-12-29` (Chaitra 2081 has 30 days; if the target month were shorter
the day would clamp to its last day).

## Period boundaries

```php
$d->startOfMonth();    $d->endOfMonth();
$d->startOfYear();     $d->endOfYear();
$d->startOfQuarter();  $d->endOfQuarter();
$d->startOfWeek();     $d->endOfWeek();    // respects config week_starts_on
$d->firstOfMonth();    $d->lastOfMonth();  // same as start/end of month

$d->startOfFiscalYear(); $d->endOfFiscalYear();  // Shrawan-based — see fiscal docs
```

## Copy with changes

```php
$d->withYear(2082);    // 2082-11-05
$d->withMonth(3);      // 2081-03-05
$d->withDay(15);       // 2081-11-15
$d->copy();            // explicit clone
```

## Comparison & classification

```php
$d->isBefore($other);  $d->isAfter($other);
$d->isBetween($a, $b);                      // inclusive
$d->isSameDay($other); $d->isSameMonth($other); $d->isSameYear($other);
$d->equals('2081-11-05');                   // accepts anything parse() accepts
$d->compareTo($other);                      // -1 / 0 / 1

$d->isToday();   $d->isTomorrow();  $d->isYesterday();
$d->isPast();    $d->isFuture();
$d->isWeekday(); $d->isWeekend();            // configurable — see business-days docs
$d->isHoliday(); $d->isBusinessDay(); $d->isWorkingDay();
```

## Relative-day navigation

```php
$d->tomorrow();           // +1 day
$d->yesterday();          // −1 day
$d->nextWeekday();        // next Monday–Friday (skips weekends only)
$d->previousWeekday();    // previous Monday–Friday
$d->nextBusinessDay();    // skips weekends **and** holidays
$d->previousBusinessDay();
```

## Diffs

```php
$d->diffInDays($other);    // signed or absolute
$d->diffInMonths($other);  // full months
$d->diffInYears($other);   // full years
$d->diffInWeeks($other);
$d->diffInHours($other);   $d->diffInMinutes($other);  $d->diffInSeconds($other);
```

Diffs accept `NepaliDate`, strings, Carbon, arrays — anything `parse()` accepts. By
default they return the **absolute** value; pass `false` as the second argument for a
signed result.

## Human-readable diffs

```php
$d->diffForHumans();                     // '१ वर्ष अघि' (config language)
$d->diffForHumans('english');            // '1 year ago'
NepaliDate::parse('2000-01-01')->age();  // full years to today
bs_diff_for_humans('2081-11-01', '2081-11-05');  // '४ दिन पछि'
```

## Calendar grid

```php
foreach ($d->calendar() as $week) {      // month grid, ready for UI calendars
    foreach ($week as $slot) {           // NepaliDate|null — null = outside the month
        // …
    }
}
```

`calendar()` respects the configurable `week_starts_on`; pass `'monday'`/`'sunday'`
explicitly to override: `$d->calendar('monday')`.

## Related values

```php
$d->fiscalYear();         // NepaliFiscalYear containing $d
$d->fiscalQuarter();      // 1–4
$d->rangeTo('2083-12-30'); // NepaliDateRange from $d to the end
```
