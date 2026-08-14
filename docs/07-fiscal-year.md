# Fiscal years & quarters

Nepal's fiscal year runs **Shrawan 1 → Ashadh end** of the next year (BS months 4 → 3).
The package models this exactly — not the Gregorian calendar year.

## `NepaliFiscalYear`

```php
use Sambat\NepaliCalendar\NepaliFiscalYear;

// From a date inside the year
$fy = NepaliFiscalYear::fromDate('2083-08-15');

// Or directly
$fy = NepaliFiscalYear::forYear(2083);
```

### Reading it

```php
$fy->year();             // 2083
$fy->label();            // '2083/84'
$fy->startDate();        // NepaliDate 2083-04-01 (Shrawan 1)
$fy->endDate();          // NepaliDate 2084-03-31 (Ashadh 31 of 2084)
$fy->days();             // 365 or 366
$fy->contains('2084-02-15');   // true — Chaitra belongs to fiscal 2083/84
$fy->isCurrent();        // true if today is inside this fiscal year
```

### Quarters

Quarters follow the fiscal year, not the calendar year:

```php
$fy->quarter();                // 1–4 (for today)
$fy->quarter(NepaliDate::parse('2083-11-05'));  // quarter containing that date

$fy->quarters();               // array of 4 NepaliDateRange
$fy->quarterRange(2);          // the 2nd quarter as a NepaliDateRange
```

Fiscal quarters are:
- Q1: Shrawan–Ashoj (months 4–6)
- Q2: Kartik–Poush (months 7–9)
- Q3: Magh–Chaitra (months 10–12)
- Q4: Baisakh–Ashadh (months 1–3 of the next year)

## On `NepaliDate`

```php
use Sambat\NepaliCalendar\NepaliDate;

$d = NepaliDate::parse('2083-08-15');

$d->fiscalYear();                 // NepaliFiscalYear — label() → '2083/84'
$d->fiscalQuarter();              // 2
$d->startOfFiscalYear();          // 2083-04-01
$d->endOfFiscalYear();            // 2084-03-31
```

## Helpers

```php
bs_fiscal_year('2083-08-15');     // NepaliFiscalYear
bs_fiscal_year()->label();        // this year's fiscal label
```

## Typical use — reports & billing

```php
$fy = NepaliFiscalYear::fromDate(now());

// This fiscal year's revenue
$sales = Sale::whereNepaliBetween('bill_date', $fy->startDate(), $fy->endDate())
    ->sum('amount');

// Quarterly breakdown
foreach ($fy->quarters() as $q) {
    $total = Sale::whereNepaliBetween('bill_date', $q->start(), $q->end())->sum('amount');
}
```

> **Note on the fiscal-year boundary:** the fiscal year ends in Ashadh (month 3) of the
> *next* BS year — the dataset can even have a 32-day Ashadh (e.g. 2084), so the package
> computes `endDate()` from the real month length rather than assuming 30/31.
