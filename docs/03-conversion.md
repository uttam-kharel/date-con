# Conversion & parsing

## AD ⇄ BS

```php
use Sambat\NepaliCalendar\NepaliDate;

// AD → BS
NepaliDate::fromAd('2025-02-17');          // 2081-11-05
NepaliDate::fromAd(Carbon::parse('2025-02-17'));

// BS → AD
$d = NepaliDate::fromBs(2081, 11, 5);
$d->ad();                                  // Carbon 2025-02-17
$d->ad()->format('Y-m-d');                 // '2025-02-17'

// Helpers
ad_to_bs('2025-02-17');                    // '२०८१-११-०५'
bs_to_ad('2081-11-05');                    // '2025-02-17'
bs_today();                                // today in BS ('Y-m-d')
```

`ad_to_bs()` / `bs_to_ad()` accept any value `NepaliDate::parse()` accepts (see below)
and take an optional format argument: `ad_to_bs('2025-02-17', 'l, F j, Y')`.

## Parsing — every accepted input

```php
NepaliDate::parse('2081-11-28');          // YYYY-MM-DD
NepaliDate::parse('2081/11/28');          // separators are flexible: -
NepaliDate::parse('2081.11.28');          //   /  .  and space all work
NepaliDate::parse('2081 11 28');          //   (and can even be mixed)
NepaliDate::parse('20811128');            // compact
NepaliDate::parse('२०८१-११-२८');          // Devanagari digits
NepaliDate::parse('2081 Falgun 28');      // romanized month name
NepaliDate::parse('2081-फागुन-28');        // Devanagari month name
NepaliDate::parse('2081-11-28 14:30:45'); // with time (kept on the AD side)

// The same flexibility applies on the AD side:
NepaliDate::fromAd('2026/01/01');         // = NepaliDate::fromAd('2026-01-01')

// Note: parse() treats input as Bikram Sambat, fromAd() as Gregorian.
NepaliDate::parse(['year' => 2081, 'month' => 11, 'day' => 28]);
NepaliDate::parse([2081, 11, 28]);
```

## Other constructors

```php
NepaliDate::today();                     // today in BS
NepaliDate::now();                       // this moment in BS
NepaliDate::fromTimestamp(1740000000);   // unix timestamp → BS
NepaliDate::fromCarbon($carbon);         // CarbonInterface → BS
NepaliDate::fromArray(['year' => 2081, 'month' => 11, 'day' => 28]);
```

## Validation

```php
NepaliDate::isValid(2081, 11, 28);       // true
NepaliDate::isValid(2081, 11, 30);       // false (Falgun 2081 has 29 days)
NepaliDate::assertValidBsDate(2081, 11, 30); // throws InvalidNepaliDateException
NepaliDate::isInAdRange('2025-02-17');   // true
```

## Supported range & accuracy

- BS: **2000-01-01 … 2100-12-30**
- AD: **1943-04-14 … 2044-04-12**

The table is anchored at **BS 2000-01-01 = AD 1943-04-14**. Years 2000–2099 are the
standard observation-based record shared by most Nepali date packages; BS 2100 is a
community-verified continuation (Panchanga Samiti based, cross-checked against Nepali
calendar sites placing Baisakh 1 2100 on April 14 2043 — exactly one day after our
verified 2099-12-30 = 2043-04-13 boundary).

| BS | AD | Why it's known |
|---|---|---|
| 2080-01-01 | 2023-04-14 | Nepali New Year 2080 |
| 2081-01-01 | 2024-04-13 | Nepali New Year 2081 |
| 2082-01-01 | 2025-04-14 | Nepali New Year 2082 |
| 2083-01-01 | 2026-04-14 | Nepali New Year 2083 |
| 2081-11-05 | 2025-02-17 | Monday 2025-02-17 |
| 2099-12-30 | 2043-04-13 | Verified boundary (previous max) |
| 2100-01-01 | 2043-04-14 | Baisakh 1 2100 (extended year, cross-checked) |
| 2100-12-30 | 2044-04-12 | Extended range boundary (new max) |

## Errors

Dates outside the range throw a typed exception — never `null` or an error string:

```php
use Sambat\NepaliCalendar\Exceptions\InvalidNepaliDateException;
use Sambat\NepaliCalendar\Exceptions\NepaliDateOutOfRangeException;

try {
    NepaliDate::fromBs(2200, 1, 1);       // outside the dataset
} catch (NepaliDateOutOfRangeException $e) {
    echo $e->getMessage();                // spells out the supported range
}

// NepaliDateOutOfRangeException extends InvalidNepaliDateException,
// which extends InvalidArgumentException — so `InvalidNepaliDateException`
// alone catches every invalid-date error.
```

## Dataset governance

The package never silently changes historical calendar data. Extending the range to
BS 2100 (v1.7.0) followed a documented process: independent source comparison, anchor
cross-checking, and a note in the CHANGELOG. The same process applies to any future
extension — see [RELEASING.md](../RELEASING.md). If you have a year beyond 2100 with a
trusted source, custom providers can supply it today (see [Installation](01-installation.md)).
