# Business days & holidays

The package never hardcodes festive dates. Weekends and holidays come from **your**
config, or from any source you bind into the container.

## Configuration

```php
// config/nepali-calendar.php

'weekend' => [6],           // PHP weekday numbers: 0 = Sunday … 6 = Saturday

'holidays' => [
    // short form: 'BS date' => 'name'
    '2083-01-01' => 'Nepali New Year',

    // long form with a type
    ['date' => '2083-10-15', 'name' => 'Dashain', 'type' => 'national'],
],
```

## On a date

```php
$d = NepaliDate::parse('2083-08-15');

$d->isWeekend();             // Saturday by default — respects the weekend config
$d->isHoliday();             // matches the configured holidays
$d->isBusinessDay();         // not weekend AND not holiday
$d->isWorkingDay();          // alias of isBusinessDay()

$d->addBusinessDays(10);     // +10 working days, skipping weekends + holidays
$d->subBusinessDays(5);      // −5 working days
$d->businessDaysUntil('2083-09-01');  // signed count of working days between

$d->nextBusinessDay();       // next working day
$d->previousBusinessDay();   // previous working day
```

## The holiday classes

For anything beyond the flat config, use the holiday layer:

```php
use Sambat\NepaliCalendar\Holidays\NepaliHoliday;
use Sambat\NepaliCalendar\Holidays\HolidayCollection;
use Sambat\NepaliCalendar\Holidays\HolidayRepository;

// A single holiday
$h = NepaliHoliday::fromConfig(['date' => '2083-10-15', 'name' => 'Dashain', 'type' => 'national']);
$h->date();    // NepaliDate
$h->name();    // 'Dashain'
$h->type();    // 'national'

// A collection — build one from loose config via the repository:
$collection = HolidayRepository::fromArray([
    '2083-01-01' => 'Nepali New Year',
    ['date' => '2083-10-15', 'name' => 'Dashain', 'type' => 'national'],
])->all();                            // HolidayCollection

// …or construct it directly from NepaliHoliday instances:
new HolidayCollection([NepaliHoliday::fromConfig('2083-01-01', 'Nepali New Year')]);

$collection->forYear(2083);          // filter to a year
$collection->contains($d);           // bool
$collection->forDate($d);            // ?NepaliHoliday
$collection->names(); $collection->dates();
$collection->count(); $collection->isEmpty();
foreach ($collection as $holiday) { /* … */ }
```

### Custom holiday sources

Bind your own repository (database, API, per-tenant) once in the app:

```php
// AppServiceProvider::register()
app()->singleton('nepali-calendar.holidays', fn () => HolidayRepository::fromArray([
    '2083-01-01' => 'Nepali New Year',
    ['date' => '2083-10-15', 'name' => 'Dashain', 'type' => 'national'],
]));
```

`HolidayRepository` also has statics for quick use:

```php
HolidayRepository::fromConfig();     // reads the published config
HolidayRepository::fromArray([…]);   // from a raw array
HolidayRepository::instance();       // the current singleton
HolidayRepository::setInstance($r);  // swap it (tests, multi-tenant)
```

Once bound, `isHoliday()` / `addBusinessDays()` / range counting all read from your
repository automatically.

## Range-level business days

```php
use Sambat\NepaliCalendar\NepaliDateRange;

$range = NepaliDateRange::between('2083-01-01', '2083-01-31');  // Baisakh, 31 days

$range->businessDays();        // list<NepaliDate> of working days
$range->workingDays();         // alias
$range->businessDayCount();    // int
$range->weekends();            // weekend days in the range
$range->holidays();            // holiday days in the range
```

## Typical use

```php
// Delivery date = 3 working days after order
$delivery = NepaliDate::parse($order->placed_date)->addBusinessDays(3);

// Monthly attendance: count working days for salary calc
$workingDays = NepaliDateRange::between($fy->startDate(), $fy->endDate())->businessDayCount();

// Report: is this a working day for the company?
if (! NepaliDate::today()->isBusinessDay()) {
    return 'Office closed';
}
```
