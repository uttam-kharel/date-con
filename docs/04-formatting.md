# Formatting

`NepaliDate::format()` implements the **full PHP `date()` token set** plus Devanagari
numerals and three languages of names.

## Basics

```php
$date = NepaliDate::parse('2081-11-05');   // Monday, 2025-02-17

$date->format('Y-m-d');                    // २०८१-११-०५ (Devanagari by default)
$date->format('Y-m-d', 'english', false);  // 2081-11-05 (western digits)
$date->format('l, F j, Y');                // सोमबार, फागुन ५, २०८१
$date->format('l, F j, Y', 'roman');       // Sombaar, Falgun 5, 2081
$date->format('l, F j, Y', 'english');     // Monday, February 17, 2025
$date->format('jS F Y');                   // ५th फागुन २०८१ (ordinals stay Latin)
$date->format('Y \y\e\a\r');               // २०८१ year (escaped literals)
$date->format('H:i:s');                    // time from the AD instant
$date->formatAd('Y-m-d');                  // 2025-02-17 — always Gregorian
```

Signature: `format(string $format, ?string $language = null, ?bool $devanagari = null)`.
`null` means "use config" (`nepali-calendar.language` / `nepali-calendar.numerals`).

## Token table

| Token | Meaning | Example (2081-11-05) |
|---|---|---|
| `Y` | Full BS year | २०८१ |
| `y` | 2-digit BS year | ८१ |
| `m` | Month, zero-padded | ११ |
| `n` | Month | ११ |
| `M` | Short month name | फागु (Nepali) / Fal (roman) / Feb (english) |
| `F` | Full month name | फागुन / Falgun / February |
| `d` | Day, zero-padded | ०५ |
| `j` | Day | ५ |
| `S` | English ordinal suffix | 5th |
| `D` | Short weekday | सोम / Mon |
| `l` | Full weekday | सोमबार / Monday |
| `N` | ISO weekday (1–7) | 1 |
| `w` | PHP weekday (0–6) | 1 |
| `z` | Day of BS year (0-based) | 313 |
| `t` | Days in BS month | २९ |
| `L` | 1 if BS leap year | 1 |
| `W` | Week number of BS year | 45 |
| `g G h H i s A a U` | Time tokens from the AD instant | 2:30:45 PM |
| `e O P T c r` | Timezone tokens from the AD instant | +05:45 |

**Escaping:** wrap literals in backslashes — `'Y \y\e\a\r'` prints `२०८१ year`.

## Named presets

Stop memorizing token strings. The `DateFormat` enum covers the common shapes:

```php
use Sambat\NepaliCalendar\Enums\DateFormat;

$date->formatPreset(DateFormat::SHORT);           // २०८१-११-०५
$date->formatPreset(DateFormat::MEDIUM);          // २०८१ फागु ०५
$date->formatPreset(DateFormat::LONG);            // फागुन ५, २०८१
$date->formatPreset(DateFormat::FULL);            // सोमबार, फागुन ५, २०८१

$date->formatPreset(DateFormat::DATETIME_SHORT);  // २०८१-११-०५ १४:३०
$date->formatPreset(DateFormat::DATETIME_MEDIUM); // २०८१ फागु ०५ १४:३०
$date->formatPreset(DateFormat::DATETIME_LONG);   // फागुन ५, २०८१ १४:३०:४५
$date->formatPreset(DateFormat::DATETIME_FULL);   // सोमबार, फागुन ५, २०८१ १४:३०:४५

$date->formatPreset(DateFormat::TIME_SHORT);      // १४:३०
$date->formatPreset(DateFormat::TIME_MEDIUM);     // १४:३०:४५
$date->formatPreset(DateFormat::TIME_FULL);       // २:३०:४५ PM
```

Presets follow the same language/numerals parameters:
`formatPreset(DateFormat::FULL, 'english', false)` → `Monday, February 17, 2025`.

## Languages

| Language | Month names | Weekday names | Uses |
|---|---|---|---|
| `nepali` (default) | फागुन | सोमबार | Devanagari names |
| `roman` | Falgun | Sombaar | Latin transliteration of the Nepali names |
| `english` | February | Monday | Names of the equivalent **AD** date |

The `CalendarLanguage` enum mirrors these: `CalendarLanguage::Nepali`,
`CalendarLanguage::Roman`, `CalendarLanguage::English`.

## Devanagari numerals

```php
nepali_number('2081');        // '२०८१'
nepali_number(1234.5);        // '१२३४.५'
english_number('२०८१');        // '2081'
english_number('१२३४.५');      // '1234.5'

$date->toNepaliNumerals();    // '२०८१-११-०५'
$date->toEnglishNumerals();   // '2081-11-05'

// The full NepaliNumber API — grouping, words and currency
NepaliNumber::format(1250000);            // '१२,५०,०००'  (Indian lakh/crore grouping)
NepaliNumber::toNepaliWords(125000);      // 'एक लाख पच्चीस हजार'
NepaliNumber::toEnglishWords(125000);     // 'one lakh twenty-five thousand'
NepaliNumber::formatCurrency(125000.5);   // 'रु. १,२५,०००.५०'
NepaliNumber::formatCurrency(125000.5, 'english'); // 'Rs. 1,25,000.50'
NepaliNumber::currencyInWords(125000.5);  // 'रुपैयाँ एक लाख पच्चीस हजार पचास पैसा मात्र'
nepali_number_words(125000);              // 'एक लाख पच्चीस हजार' (helper)
```

## Bilingual output

`formatBoth()` renders the same instant in both calendars — BS with Nepali names and
the equivalent AD date in English:

```php
$date->formatBoth();
// [
//   'nepali'  => 'सोमबार, फागुन ५, २०८१',
//   'english' => 'Monday, February 17, 2025',
// ]
```

## Serialization

```php
(string) $date;                // '२०८१-११-०५' (uses default_format)
$date->toDateString();         // '2081-11-05'
$date->toDateString('/');      // '2081/11/05'
$date->toArray();              // ['year' => 2081, 'month' => 11, 'day' => 5, ...]
$date->toJson();               // JSON of toArray()
$date->jsonSerialize();        // same, for json_encode()
```
