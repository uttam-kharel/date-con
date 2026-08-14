# Quick start

Five minutes to a working BS date in your app. Everything here works identically in
plain PHP and Laravel.

## 1. Create a Nepali date

```php
use Sambat\NepaliCalendar\NepaliDate;

// From a BS date
NepaliDate::fromBs(2081, 11, 5);
NepaliDate::parse('2081-11-05');

// From an AD / Gregorian value
NepaliDate::fromAd('2025-02-17');
NepaliDate::fromAd(Carbon::parse('2025-02-17'));

// The current moment
NepaliDate::today();
NepaliDate::now();
```

## 2. Read it

```php
$d = NepaliDate::parse('2081-11-05');   // Monday, 17 Feb 2025

$d->year();          // 2081
$d->month();         // 11  (1 = Baisakh … 12 = Chaitra)
$d->day();           // 5
$d->monthName();     // फागुन
$d->weekDayName();   // सोमबार
$d->weekDayIso();    // 1 (Monday)
$d->dayOfYear();     // 314
$d->daysInMonth();   // 29
$d->daysInYear();    // 366
$d->isLeapYear();    // true
$d->age();           // full years to today
```

## 3. Format it

```php
echo $d;                                    // २०८१-११-०५
$d->format('Y-m-d');                        // २०८१-११-०५
$d->format('Y-m-d', 'english', false);      // 2081-11-05 (Latin digits)
$d->format('l, F j, Y');                    // सोमबार, फागुन ५, २०८१
$d->format('l, F j, Y', 'roman');           // Sombaar, Falgun 5, 2081
$d->format('l, F j, Y', 'english');         // Monday, February 17, 2025
$d->formatAd('Y-m-d');                      // 2025-02-17 (the AD side)
$d->formatPreset(\Sambat\NepaliCalendar\Enums\DateFormat::FULL);
                                            // सोमबार, फागुन ५, २०८१
```

See [Formatting](04-formatting.md) for the full token table and presets.

## 4. Do math (immutable)

```php
$d->addDays(10)->subMonths(1)->addYears(1);   // chains; $d never changes
$d->addMonths(1);                             // clamps: 2081-11-29 + 1 month = 2081-12-29
$d->startOfMonth();                           // 2081-11-01
$d->endOfMonth();                             // 2081-11-29
$d->nextDay()->previousMonth();
$d->diffInDays(NepaliDate::parse('2081-12-05')); // 30
$d->diffForHumans();                          // १ वर्ष अघि
```

## 5. Convert to and from AD

```php
$ad = $d->ad();                       // Carbon instance of the same instant
ad_to_bs('2025-02-17');               // '२०८१-११-०५'
bs_to_ad('2081-11-05');               // '2025-02-17'
```

## 6. Ranges, fiscal years & recurrence

```php
use Sambat\NepaliCalendar\NepaliDateRange;
use Sambat\NepaliCalendar\NepaliFiscalYear;
use Sambat\NepaliCalendar\Recurrence;

$range = NepaliDateRange::between('2081-05-01', '2081-07-30'); // inclusive
$range->count();                       // 91
foreach ($range->days() as $day) { /* NepaliDate */ }

$fy = NepaliFiscalYear::fromDate('2083-08-15');
$fy->label();                          // '2083/84'

$billing = Recurrence::monthly('2083-01-05')->every(2)->until('2083-12-30');
foreach ($billing as $date) { /* … */ }
```

## 7. Laravel extras

```php
// validation
'bill_date' => ['required', 'nepali_date_between:2083-01-01,2083-12-30'],

// Eloquent cast (canonical AD storage, BS presentation)
protected $casts = ['bill_date' => \Sambat\NepaliCalendar\Casts\NepaliDateCast::class];

// query helpers
Report::whereNepaliMonth('bill_date', 2081, 11)->get();

// Blade
@nepaliDate($invoice->bill_date, 'l, F j, Y')

// Carbon macro
Carbon::parse('2025-02-17')->toNepaliDate();
```

All of this is covered in detail in [Laravel](09-laravel.md).

## Next steps

- [Conversion & parsing](03-conversion.md) — every input format, the supported range
- [Formatting](04-formatting.md) — tokens, presets, languages, numerals
- [Dates](05-dates.md) — the full `NepaliDate` API
- [Date ranges](06-date-ranges.md) — ranges, operations, export
- [Fiscal year](07-fiscal-year.md) — Shrawan-based fiscal years & quarters
- [Business days & holidays](08-business-days.md) — weekends, holidays, business-day math
- [Laravel](09-laravel.md) — validation, casts, query helpers, Blade, Carbon, artisan
