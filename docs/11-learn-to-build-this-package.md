# Learn to Build This Package — PHP, OOP, Laravel & Publishing, From Zero

> **Who is this for?** You know *simple PHP* — `if`, loops, functions, maybe a little HTML
> inside PHP files. You do **not** yet know classes, objects, namespaces, Composer, or
> Laravel's package ecosystem. This file teaches all of it, using **this very package**
> (`sambat/nepali-calendar`) as the example. Every code sample below is real code that
> exists in this repository — read the linked files as you go.
>
> **How to read it:** Don't rush. Each section builds on the one before. The exercises at
> the end (and after each big section) are the real learning — do them.

---

## Table of contents

1. [The big picture — what did we actually build?](#1-the-big-picture)
2. [PHP foundations — classes, objects, autoloading](#2-php-foundations)
3. [Composer — the package manager](#3-composer)
4. [Design patterns in this package](#4-design-patterns)
5. [The Laravel layer — service providers & friends](#5-the-laravel-layer)
6. [Testing — Pest & Testbench](#6-testing)
7. [Versioning, tags & releases](#7-versioning-tags--releases)
8. [Hosting & publishing — GitHub → Packagist](#8-hosting--publishing)
9. [How to make *better* packages](#9-how-to-make-better-packages)
10. [Glossary](#10-glossary)
11. [Exercises](#11-exercises)

---

## 1. The big picture

A **PHP package** is just a folder of PHP files that other people can install with
Composer. That's the whole trick. When someone runs:

```bash
composer require sambat/nepali-calendar
```

Composer downloads this repository into their project's `vendor/` folder, and then their
code can call our classes. Everything else — autoloading, Laravel integration, tests,
tags, publishing — exists to make that one moment safe and pleasant.

### The anatomy of this package

```
nepali-calendar/
│
├── composer.json              ← THE identity card. Name, version rules, autoloading.
├── config/nepali-calendar.php ← default settings (published into user's app on demand)
├── database/migrations/       ← SQL table recipe for the optional "database" driver
│
├── src/                       ← all the real code ("library" code, plain PHP + interfaces)
│   ├── NepaliDate.php           the main class — an immutable BS date value object
│   ├── NepaliDateFactory.php    Laravel-friendly entry point (used by the facade)
│   ├── NepaliNumber.php         numerals + number-to-words (static utility class)
│   ├── NepaliDateRange.php      date ranges (start … end)
│   ├── Recurrence.php           "every month", "every Friday" rules
│   ├── NepaliFiscalYear.php     Shrawan-based fiscal years (2083/84)
│   ├── Calendar.php             the conversion engine (AD ⇄ BS)
│   ├── helpers.php              global shortcut functions: bs_today(), ad_to_bs(), …
│   ├── Contracts/               interfaces — the "shape" contracts
│   ├── Providers/               implementations of those contracts (algorithm, database)
│   ├── Enums/                   Season, Rashi, NepaliMonth, DateFormat, CalendarLanguage
│   ├── Exceptions/              typed errors
│   ├── Rules/                   Laravel validation rules
│   ├── Casts/                   Laravel Eloquent casts
│   ├── Query/                   Laravel query builder macros
│   ├── Commands/                artisan commands (nepali:convert, nepali:seed, …)
│   ├── Support/                 Parser, Formatter, Config fallbacks, Blade helpers
│   └── NepaliCalendarServiceProvider.php ← the Laravel glue
│
├── tests/                      Pest test suite
└── .github/workflows/          CI (tests on every push) + release automation
```

Two layers to keep separate in your head:

1. **Core layer** (`NepaliDate`, `Calendar`, …) — pure PHP, knows nothing about Laravel.
   It would work in any PHP project. **This is the part that has real value.**
2. **Laravel layer** (ServiceProvider, Rules, Casts, Commands, helpers) — glue that plugs
   the core into a Laravel app. This part is *optional* and *thin* — it only exists to
   make the core convenient inside Laravel.

> **Rule #1 of good packages:** make the core framework-free, then add optional glue.
> Never force people to use Laravel just to get a date conversion.

---

## 2. PHP foundations

Everything in `src/` is **object-oriented PHP**. If you've only written procedural PHP
(functions + `$variables`), here is the mental shift:

### 2.1 What is a class? A blueprint.

A **class** is a blueprint. An **object** is a real thing made from that blueprint.

```php
// src/NepaliDate.php (simplified) — the blueprint
final class NepaliDate
{
    private readonly int $year;   // a property — data each object owns
    private readonly int $month;
    private readonly int $day;

    public function __construct(int $year, int $month, int $day)  // constructor
    {
        $this->year = $year;      // $this = "the object currently being built"
        $this->month = $month;
        $this->day = $day;
    }

    public function year(): int   // a method — behavior
    {
        return $this->year;
    }
}
```

Using it:

```php
$date = new NepaliDate(2083, 4, 29);   // make an object ("instantiate")
echo $date->year();                     // 2083
```

Compare to procedural PHP:

```php
$year = 2083; $month = 4; $day = 29;    // three loose variables
```

The class bundles the data **and** the rules about that data into one unit. Nobody can
hold a "date" that is missing a month, because the constructor requires all three parts.

### 2.2 Vocabulary, one by one

| Term | Meaning | Example in this package |
|---|---|---|
| **class** | blueprint | `NepaliDate`, `Recurrence`, `NepaliDateRange` |
| **object** | one thing made from a blueprint | `$date = NepaliDate::today()` |
| **property** | data stored on an object | `$this->year` |
| **method** | function on an object | `$date->format('Y-m-d')` |
| **constructor** | code that runs when an object is created | `__construct()` — validates the date |
| **visibility** | who may touch something | `public` (everyone), `private` (only the class itself), `protected` (class + children) |
| **static** | belongs to the class, not any one object | `NepaliDate::today()` — called with `::` not `->` |
| **final** | this class cannot be extended | `final class NepaliDate` |
| **readonly** | property set once, never changed again | `private readonly int $year` |
| **namespace** | full "address" of a class | `Sambat\NepaliCalendar\NepaliDate` |
| **use** | import a namespace so you can type the short name | `use Sambat\NepaliCalendar\NepaliDate;` |
| **interface** | a promise: "anything that says it implements me must have these methods" | `Contracts\CalendarDataProvider` |
| **enum** | a fixed list of named values (PHP 8.1+) | `Season::Barsha`, `DateFormat::LONG` |
| **exception** | a way to signal an error loudly | `InvalidNepaliDateException` |
| **type hint** | say exactly what a parameter/return must be | `function year(): int` |
| **strict_types** | PHP refuses to silently coerce types | `declare(strict_types=1);` at the top of every file |

### 2.3 Static vs instance — when to use which

```php
// STATIC: no object needed. Like a function that lives on a class.
NepaliDate::today();                      // "today" — same answer for everyone
NepaliNumber::toNepali(2083);             // २०८३ — pure conversion, no state

// INSTANCE: needs an actual date object to do its job.
$date->addDays(10);                       // depends on WHICH date
$date->format('j F Y');                   // depends on WHICH date
```

Rule of thumb: if the method needs to know *which* date, it's an instance method. If it's
a pure calculation or a "give me a new date" factory, it's static.

### 2.4 Immutability — the single most important idea in this package

Look at this:

```php
$d = NepaliDate::fromBs(2083, 4, 29);
$new = $d->addDays(10);    // <-- returns a NEW object
echo $d->format('Y-m-d');  // still 2083-04-29! $d was NOT changed
echo $new->format('Y-m-d'); // 2083-05-09
```

`$d` never changes. Every "modification" method returns a **brand-new object** and leaves
the old one alone. That's what `readonly` properties enforce — the compiler physically
cannot let you change `$d->year` after construction.

**Why does this matter?**
- No surprise bugs: passing a date to a function can't mutate your copy.
- Safe to share: you can hand the same `NepaliDate` to ten places and they can all
  "modify" it without corrupting each other.
- Easy to reason about: the object always means exactly one date, forever.

This is the **value object** pattern (see §4.1). `Carbon` (the popular PHP date library)
is mutable; this package deliberately chose immutable because immutable is safer.

### 2.5 Named constructors — making `new` friendlier

`new NepaliDate(2083, 4, 29)` is fine, but what about "today"? or "from a Gregorian
date"? You can't have two `__construct` methods in PHP. The solution is **named
constructors** — static methods that build objects:

```php
NepaliDate::fromBs(2083, 4, 29);              // from BS parts
NepaliDate::fromAd('2026-08-14');             // from Gregorian
NepaliDate::parse('भोलि');                    // from anything (tomorrow)
NepaliDate::createFromFormat('j F Y', '29 साउन 2083');
NepaliDate::fromTimestamp(1740000000);        // from unix timestamp
NepaliDate::fromCarbon($carbon);
NepaliDate::today();
NepaliDate::now();
```

Each named constructor is a tiny, readable factory. Inside, they all end at the
`__construct`, which does the validation. This is called the **factory method pattern**.

### 2.6 Namespaces & autoloading (PSR-4)

`composer.json` contains this:

```json
"autoload": {
    "psr-4": {
        "Sambat\\NepaliCalendar\\": "src/"
    },
    "files": ["src/helpers.php"]
}
```

This is a **promise**: *any class named `Sambat\NepaliCalendar\Xxx` lives in
`src/Xxx.php`*. The namespace maps to the folder. So:

- `Sambat\NepaliCalendar\NepaliDate` → `src/NepaliDate.php`
- `Sambat\NepaliCalendar\Enums\Season` → `src/Enums/Season.php`
- `Sambat\NepaliCalendar\Rules\NepaliDateRule` → `src/Rules/NepaliDateRule.php`

Because of PSR-4, **you never write `require` again**. When code says
`new NepaliDate(...)`, PHP (via Composer's autoloader) figures out the file path from the
class name and loads it automatically. The `files` entry is the exception — `helpers.php`
is loaded *eagerly* on every request because it defines global functions, and global
functions can't be autoloaded (PHP has no "function autoloading").

> **Try it:** `composer dump-autoload` regenerates the autoloader after you move files.

### 2.7 Enums — fixed choices (PHP 8.1)

```php
// src/Enums/Season.php
enum Season: string
{
    case Barsha = 'barsha';     // वर्षा
    case Shishir = 'shishir';   // शिशिर
    // ...

    public function devanagari(): string
    {
        return match ($this) {
            self::Barsha => 'वर्षा',
            self::Shishir => 'शिशिर',
            // ...
        };
    }
}
```

Why not just strings? Because `'barsha'`, `'Barsha'`, `'bArsha'` are all "valid" strings,
but an enum allows **only the listed values** — typos become errors at compile time, not
bugs at runtime. Enums replaced the old trick of `class Season { const BARSHA = 'barsha'; }`.

### 2.8 Exceptions — loud errors, never silent `false`

Old-style PHP code returns `false` or `null` on failure, and the caller forgets to check.
This package **throws** instead:

```php
NepaliDate::fromBs(2083, 4, 33);  // day 33 of a 30/31-day month
// PHP throws: InvalidNepaliDateException "Day 33 is out of range ..."
```

If you don't catch it, the app crashes loudly with a clear message. That's *good* — a
crash you see is better than bad data you don't. And because the exception is *typed*
(`InvalidNepaliDateException`), callers can catch specifically:

```php
try {
    $d = NepaliDate::fromBs($y, $m, $d);
} catch (InvalidNepaliDateException $e) {
    // show a friendly "invalid date" message to the user
}
```

### 2.9 `final`, `readonly`, `declare(strict_types=1)` — why so strict?

Every file starts with `declare(strict_types=1);` — PHP won't quietly turn `"2083"` into
`2083`; it demands the right type. Classes are `final` — nobody can subclass and quietly
break the guarantees. Properties are `readonly` — set once. This is **defensive design**:
the package is small, so it can afford to be rigid, and rigidity is what makes a date
library trustworthy. A date library that silently "fixes" your data is a bug factory.

---

## 3. Composer

Composer is PHP's package manager (like `npm` for Node). It reads `composer.json`,
downloads dependencies, and generates the autoloader.

### 3.1 Reading our composer.json line by line

| Key | Meaning | Ours |
|---|---|---|
| `name` | `vendor/package` — globally unique | `sambat/nepali-calendar` |
| `type` | `library` (not an app) | `library` |
| `license` | legal terms | `MIT` (anyone can use it, even commercially) |
| `require` | **runtime** dependencies | PHP ^8.1–8.5, illuminate/contracts + support ^10–13, Carbon ^2.72/^3 |
| `require-dev` | **only needed to develop/test**, not installed for users | Pest, Testbench, Pint |
| `suggest` | "you might also want this" | illuminate/database for the DB driver |
| `autoload` | PSR-4 mapping + eager files | `Sambat\NepaliCalendar\` → `src/` |
| `autoload-dev` | autoloading for tests only | test namespace → `tests/` |
| `extra.laravel` | Laravel auto-discovery! | provider class + facade alias |
| `scripts` | shortcuts: `composer test`, `composer pint` | runs Pest / Pint |
| `config.allow-plugins` | trust Pest's plugin | pestphp/pest-plugin |

### 3.2 Version constraints — the `^`, `|`, `~` language

```
"php": "^8.1 || ^8.2 || ^8.3 || ^8.4 || ^8.5"
"illuminate/support": "^10.0|^11.0|^12.0|^13.0"
"nesbot/carbon": "^2.72|^3.0"
```

- `^10.0` means ">= 10.0.0 and < 11.0.0" — **any 10.x**, because 10.x is guaranteed
  backward-compatible within the major.
- `^10.0|^11.0` means "any 10.x **or** any 11.x".
- `^2.72|^3.0` means "Carbon 2.72+ or Carbon 3" — we support both major lines.

This is the **support matrix**, and it's a promise: anyone on PHP 8.2 with Laravel 11 can
install us; anyone on PHP 8.5 with Laravel 13 can too. Supporting a wide range costs you
testing effort (see §6) but makes the package dramatically more useful. Never write
`"illuminate/support": "*"` — that means "anything ever", including tomorrow's breaking
change.

### 3.3 `require` vs `require-dev` vs `suggest` — the art of staying light

- **`require`**: what users get. Ours is tiny: two Illuminate packages (interfaces +
  helpers) and Carbon. **The core conversion works with zero dependencies.**
- **`require-dev`**: only installed when *developing the package itself* (Pest tests,
  Testbench to fake a Laravel app, Pint to format code).
- **`suggest`**: the DB driver needs the database package — but only if you choose that
  driver. So it's a suggestion, not a requirement.

> **Rule #2 of good packages:** keep `require` small. Every dependency is a version
> conflict waiting to happen in someone's app. If a feature can be optional, make it
> optional (`suggest` + a guard like `class_exists(...)`).

### 3.4 The `extra.laravel` magic — auto-discovery

```json
"extra": {
    "laravel": {
        "providers": ["Sambat\\NepaliCalendar\\NepaliCalendarServiceProvider"],
        "aliases": {"NepaliDate": "Sambat\\NepaliCalendar\\Facades\\NepaliDate"}
    }
}
```

Laravel scans installed packages for this block. When found, it **automatically registers
our service provider** and the `NepaliDate` alias — the user installs the package and
everything just works, with zero manual configuration. That's why the Laravel layer exists
as a separate service provider class: it's the hook Laravel looks for.

---

## 4. Design patterns

A "design pattern" is a *named, proven solution to a recurring problem*. Knowing the
names helps you talk about code and recognise good structure. This package uses these:

### 4.1 Value object (NepaliDate)

**Problem:** dates are everywhere; bugs hide in comparisons and mutation.
**Solution:** an immutable object whose *identity is its value* — two dates are "equal"
if they hold the same year/month/day. It carries its own validation (can't exist if
invalid), its own formatting, its own arithmetic. There is no separate "helper" full of
loose date functions — the object does its own job.

### 4.2 Factory method / named constructors (§2.5)

**Problem:** `new` can't express "today" or "from Gregorian".
**Solution:** static methods that *name* each way of building. `NepaliDateFactory` (§5.3)
is the Laravel container-friendly version of the same idea.

### 4.3 Strategy / Provider pattern (CalendarDataProvider)

This is the most important architectural decision in the package, and it exists because of
a question you asked earlier: *"config option to user either he will use database or not"*.

**Problem:** the BS month-length table could come from a hardcoded constant array, from a
database table, or from an API. We don't want the conversion engine to care.

**Solution:** an **interface** defines the shape:

```php
// src/Contracts/CalendarDataProvider.php
interface CalendarDataProvider
{
    public function allMonthLengths(): array;   // month lengths per year
    public function minYear(): int;
    public function maxYear(): int;
}
```

Two implementations exist: `ArrayCalendarDataProvider` (built-in constants — **no
database needed**) and `DatabaseCalendarDataProvider` (reads the seeded table). The
conversion engine (`Calendar`) only ever talks to the *interface* — it has no idea which
one is active. The user picks with a config value:

```php
// config/nepali-calendar.php
'driver' => env('NEPALI_CALENDAR_DRIVER', 'algorithm'),   // or 'database'
```

If the user wants a *custom* source (their own API), they implement the interface and
bind it in the container. Zero changes to the engine.

> **Rule #3 of good packages:** program to an interface, not an implementation. The
> interface is the seam where other people plug in. One implementation is a guess; two
> implementations prove the seam is real.

### 4.4 Dependency injection & the service container

In plain PHP, `Calendar` can ask for a provider like this:

```php
Calendar::setProvider($myProvider);   // give me YOUR data source
```

In Laravel, the **service container** manages this. The provider file says:

```php
$this->app->singleton(ArrayCalendarDataProvider::class);
$this->app->singleton(DatabaseCalendarDataProvider::class);

// "when someone asks for the calendar provider, build the one the config selects"
$this->app->bind('nepali-calendar.provider', function () {
    $driver = strtolower((string) config('nepali-calendar.driver', 'algorithm'));
    return match ($driver) {
        'database', 'db' => $this->app->make(DatabaseCalendarDataProvider::class),
        default          => $this->app->make(ArrayCalendarDataProvider::class),
    };
});
```

**Dependency injection (DI)** = don't let objects create their own dependencies; *give*
them what they need. **The container** = Laravel's magic drawer: you tell it how to build
things once, and anywhere in the app you ask for them, it hands you the same (or a fresh)
instance. `singleton` = "one instance for the whole app"; `bind` = "build fresh each
time". The container is *the* most important Laravel concept — every controller, job,
and service in a Laravel app is resolved through it.

### 4.5 Facade (NepaliDate)

A **facade** is a shortcut: a static-looking call that secretly reaches into the
container. Instead of:

```php
$factory = app(NepaliDateFactory::class);
$factory->today();
```

You write:

```php
NepaliDate::today();   // the facade — same thing, prettier
```

`src/Facades/NepaliDate.php` is a tiny class that extends Laravel's `Facade` and declares
`protected static function getFacadeAccessor(): string { return 'nepali-date'; }`. The
container binding in `register()` maps the string `'nepali-date'` to a
`NepaliDateFactory`. The facade is *only* sugar — it exists for developer convenience.

> **Rule #4:** facades are fine for *your own package's* entry points, but never hide
> real logic inside them. The facade must be a thin alias to a real class.

### 4.6 Fluent / builder pattern (Recurrence)

```php
Recurrence::monthly(NepaliDate::today())
    ->every(2)          // every 2 months
    ->on(1, 15)         // on the 1st and 15th
    ->until('2085-12-30')
    ->take(24);         // max 24 occurrences
```

Each method returns `$this`, so calls chain. The name: it *reads like a sentence*. Every
`->` call either sets a rule or returns the same builder. The guardrail
(`Recurrence::MAX_OCCURRENCES = 10000`) is there because a "repeat forever" rule could
otherwise generate millions of dates and kill the server.

### 4.7 Global helper functions (helpers.php)

```php
bs_today();                  // '2083-04-29'
ad_to_bs('2026-08-14');      // '2083-04-29'
nepali_number(125000);       // '१,२५,०००'
bs_age('2000-01-01');        // 26 (full years)
```

Why both classes **and** functions? Because in Blade templates and quick scripts,
`nepali_number($x)` is friendlier than `NepaliNumber::toNepali($x)`. The functions are
one-liners that delegate to the classes — they never contain logic themselves (see
Rule #4). Note the naming: Laravel's own helpers use snake_case (`config()`, `view()`),
so package helpers follow the convention.

### 4.8 Which pattern when? A decision list

| You want to… | Use |
|---|---|
| Represent a thing with rules (a date, an amount) | **Value object** (immutable) |
| Build objects in readable ways | **Named constructors / factory** |
| Swap data sources without touching logic | **Interface + Strategy/Provider** |
| Let Laravel hand out instances | **Container bindings + DI** |
| Give users a pretty global entry point | **Facade + helpers** |
| Chain rules readably | **Fluent builder** |
| Make invalid states impossible | **Typed exceptions + validation in constructor** |

---

## 5. The Laravel layer

Laravel is a framework built around one idea: **the service container** — a box of
instructions for building things, plus conventions for where code lives. A "package" is
just code that plugs into that box. The plug is the **service provider**.

### 5.1 ServiceProvider — `register()` vs `boot()`

Every Laravel package has one. Two methods, two timings:

```php
public function register(): void
{
    // EARLY: only container setup. NO using other features yet.
    $this->mergeConfigFrom(__DIR__.'/../config/nepali-calendar.php', 'nepali-calendar');
    $this->app->singleton('nepali-date', fn () => new NepaliDateFactory);
    // ...
}

public function boot(): void
{
    // LATER: everything is loaded — safe to touch Blade, Validator, routes, views.
    $this->commands([...]);          // register artisan commands
    $this->publishes([...]);         // allow `php artisan vendor:publish`
    $this->registerBladeDirectives(); // add @nepaliDate(...)
    // ...
}
```

- **`register()`** = tell the container *how to build things*. Nothing else.
- **`boot()`** = do things that need a fully-loaded app.

**Mistake beginners make:** putting real work in `register()`. It runs before other
services exist; if your code touches Blade there, it breaks. When in doubt: container
bindings in `register`, everything else in `boot`.

### 5.2 Config — merge, publish, read

The package ships `config/nepali-calendar.php`. Two directions:

```php
// register(): merge OUR defaults under Laravel's config (so config() always works,
// even before the user publishes anything)
$this->mergeConfigFrom(__DIR__.'/../config/nepali-calendar.php', 'nepali-calendar');

// boot(): let the user COPY our config into their app so they can edit it
$this->publishes([
    __DIR__.'/../config/nepali-calendar.php' => config_path('nepali-calendar.php'),
], 'nepali-calendar-config');
```

The user runs `php artisan vendor:publish --tag=nepali-calendar-config` and gets a
`config/nepali-calendar.php` file they can edit. Until then, `config('nepali-calendar.driver')`
returns our default. **Merging** = defaults that work out of the box; **publishing** =
power to customize. Both matter.

### 5.3 The facade + factory (§4.5)

The chain for `NepaliDate::today()` inside Laravel:

```
NepaliDate::today()                 ← Facade (static sugar)
  → container['nepali-date']        ← binding registered in register()
  → NepaliDateFactory->today()      ← thin factory class
  → NepaliDate::today()             ← the real, framework-free class
```

The core never learns about Laravel; the glue (facade + factory) is what bridges them.

### 5.4 Validation rules

Laravel lets packages add rules:

```php
Validator::extend('nepali_date', function (string $attribute, mixed $value): bool {
    try {
        NepaliDate::parse($value);
        return true;
    } catch (\Throwable) {
        return false;
    }
});
```

Now users write `'date' => ['required', 'nepali_date']` in their Form Requests. The rule
is *defined* in the provider's `boot()` and delegates straight to the core parser. There
are also **rule classes** (`Rules\NepaliDateRule`) for the modern
`new NepaliDateRule` syntax and for ranged rules (before/after/between).

### 5.5 Eloquent casts

```php
protected $casts = [
    'bill_date' => NepaliDateCast::class,
];
```

A cast class has two methods: `get()` (database value → your object) and `set()` (your
object → database value). Our cast stores the **AD date** in the database (that's the
canonical, sortable truth) and presents a **NepaliDate** to the app:

```php
$bill->bill_date;              // a NepaliDate object! (presentation)
$bill->bill_date->format('Y-m-d');  // 2083-04-29
```

> **Rule #5 — a genuinely important design decision:** store the *Gregorian/AD* date in
> the database, convert to BS at the presentation layer. Why? Databases, sorting,
> `whereBetween`, and every other tool understand one universal timeline. BS is a display
> format. If you store BS, "which bills fall in this week?" becomes a nightmare. This is
> the kind of decision that separates good packages from clever ones.

### 5.6 Query builder macros

```php
Builder::macro('whereNepaliDate', function (string $column, mixed $date) {
    // convert the BS date to AD and query the canonical AD column
});
```

This lets users write `Bill::whereNepaliDate('bill_date', '2083-04-01')->get()` — the
macro translates BS to the stored AD column behind the scenes. Macros = "add a method to
an existing class without touching it". Powerful, but use sparingly: they're global and
can collide.

### 5.7 Blade directives

```php
Blade::directive('nepaliDate', function (string $expression) {
    return "<?php echo \Sambat\NepaliCalendar\Support\Blade::render({$expression}); ?>";
});
```

In a view: `@nepaliDate($bill->bill_date, 'Y-m-d')`. A directive is literally a string
replacement: Blade turns `@nepaliDate(...)` into the PHP `echo` you see above. Note the
*delegation* to a real class (`Blade::render()`) — never write logic inside the closure.

### 5.8 Carbon macros

Carbon (the standard PHP date library) is extended so users of *both* libraries get
BS for free:

```php
$carbon->toNepaliDate();            // → NepaliDate
$carbon->formatNepali('j F Y');     // '२९ साउन २०८३'
```

`Carbon::macro(...)` works like `Builder::macro` — adding methods to Carbon. And our own
`NepaliDate::fromCarbon($carbon)` goes the other direction, so the two libraries interop
seamlessly.

### 5.9 Artisan commands

```php
php artisan nepali:today
php artisan nepali:convert 2026-08-14
php artisan nepali:seed
php artisan nepali:info
```

Commands are classes with a `signature` (how it's called) and a `handle()` method.
`nepali:seed` is special: it fills the *optional* database driver's table with the BS
dataset — the command makes the "database or no database" choice (your question!) usable
without writing SQL by hand. `nepali:info` also registers into `php artisan about` so the
package version shows in the app's system report.

### 5.10 The Laravel 10 compatibility lesson (a true story)

The provider initially called `$this->publishesMigrations(...)` — a method that only
exists in **Laravel 11+**. On Laravel 10, the package crashed at boot with
`Call to undefined method`. Our CI initially didn't test Laravel 10, so nobody noticed.
The fix is the pattern you'll see all over mature packages — **feature detection**:

```php
if (method_exists($this, 'publishesMigrations')) {
    $this->publishesMigrations([...]);
} else {
    $this->publishes([...]);   // the classic way, works everywhere
}
```

> **Rule #6:** if you claim to support a version, **test it in CI**. `method_exists` /
> `class_exists` guards are how one codebase politely supports old and new versions at
> once.

---

## 6. Testing

### 6.1 Pest — a friendly test framework

Tests live in `tests/`, written with [Pest](https://pestphp.com), which reads like
English:

```php
it('converts AD to BS', function (): void {
    $date = NepaliDate::fromAd('2026-08-14');

    expect($date->year())->toBe(2083)
        ->and($date->month())->toBe(4)
        ->and($date->day())->toBe(29);
});

it('rejects impossible dates', function (): void {
    NepaliDate::fromBs(2083, 4, 33);
})->throws(InvalidNepaliDateException::class);
```

Run everything with `composer test` (which is `vendor/bin/pest`). 198 tests / 2,873
assertions right now. The most valuable tests are the **round-trip** ones:

```php
// AD → BS → AD must come back to the same day. If the dataset is wrong anywhere,
// these fail. This is the safety net for the entire calendar table.
expect(NepaliDate::fromAd($ad)->ad()->format('Y-m-d'))->toBe($ad);
```

### 6.2 Testbench — fake a Laravel app inside the package

Packages can't install a whole Laravel app just to test. **Orchestra Testbench** boots a
minimal fake Laravel app *inside the package's own test suite*. Our `tests/TestCase.php`
extends Testbench's base, and the service provider under test is registered
automatically. That's how we test rules, casts, macros and commands without a real app.

### 6.3 The CI matrix — why several PHP × Laravel combos

GitHub Actions runs our tests on **PHP 8.2 / 8.3 / 8.4 / 8.5 × Laravel 11 / 12 / 13**
(newest Laravel on newest PHP). Each combo is a fresh `composer install` + `pest` run.
This is the proof behind the support matrix in composer.json — a claim like "supports
Laravel 13" is only true if CI *runs* it.

**A hard truth we learned:** PHP 8.1 and Laravel 10 are now *impossible* to test — PHP 8.1
is end-of-life, its last compatible Pest version conflicts with newer PHPUnit security
releases, and Laravel 10 stable releases are blocked by security advisories in modern
Composer. The code still *runs* on 8.1 (composer.json still allows it), but CI can't
prove it anymore. **Support lifecycle matters:** never promise forever. (See §7.)

---

## 7. Versioning, tags & releases

### 7.1 Semantic Versioning (SemVer) — `MAJOR.MINOR.PATCH`

```
v1.14.0
 │  │  └─ PATCH  = bug fix only (nothing new, nothing broken)        → v1.14.1
 │  └──── MINOR  = new feature, backward-compatible                  → v1.15.0
 └─────── MAJOR  = breaking change (API or PHP floor moved)          → v2.0.0
```

These rules are a **contract with your users**:
- `composer update` never breaks them (patch/minor are safe).
- A major version tells them "read the migration guide".
- Pre-releases look like `v2.0.0-beta.1`, `v2.0.0-rc.1`.

### 7.2 Tags = immutable release points

A git **tag** freezes a commit forever:

```bash
git tag -a v1.14.0 -m "v1.14.0 — reverse parsing & comparisons"
git push origin main --tags
```

Anyone can now `composer require sambat/nepali-calendar:^1.14` and get exactly that
commit. Rules we follow:
- Tags are **never** moved or deleted once pushed (immutable history).
- Every tag = a `CHANGELOG.md` entry + the provider `VERSION` constant matching.
- `bin/check-release.php` **verifies** this automatically before a release ships.

### 7.3 The release pipeline (what happens when you push a tag)

`.github/workflows/` contains two workflows:
- `tests.yml` — runs on every push **and every tag**: the full PHP × Laravel matrix.
- `release.yml` — on tag push: `bin/check-release.php` (version matches tag?) →
  `composer validate` → full tests → Pint → **creates the GitHub Release**.

So "make a release" = create a tag and push. Everything else is automatic. `RELEASING.md`
is the human checklist (choose the next version number, write the CHANGELOG entry, bump
`VERSION`, tag, push, watch CI).

---

## 8. Hosting & publishing

### 8.1 Hosting on GitHub

```bash
git remote add origin https://github.com/you/your-package.git
git push -u origin main
```

That's it — the code is hosted. Add the badge row to your README (PHP version, Laravel
version, latest tag, CI status, license) — the shields update themselves from live data.

### 8.2 Publishing on Packagist (so `composer require` works for everyone)

`composer require sambat/nepali-calendar` only works worldwide once the package is on
**Packagist** (the public registry, like npmjs). Steps:

1. Create an account at [packagist.org](https://packagist.org) (connect GitHub).
2. **Submit** → paste the GitHub repo URL. Packagist reads `composer.json` — the `name`
   must match the repo's intended name and must be *unique*.
3. **Connect the webhook** (Packagist gives you a URL + token → add to the repo's GitHub
   webhooks). Now every pushed tag auto-publishes a new version.
4. First release = the whole history appears; add the Packagist badge to the README.

**Important:** the name is claimed on Packagist (case-insensitive) — check availability
*before* building a whole ecosystem around a name. Our package is **not on Packagist yet**
— it currently installs from GitHub via `"repositories"` config or VCS. Publishing is a
5-minute task once you decide the name is final.

### 8.3 What a professional package repo contains

| File | Purpose |
|---|---|
| `README.md` | what it is, one example, badges, docs links |
| `CHANGELOG.md` | every version's Added/Changed/Fixed — keep-a-changelog format |
| `LICENSE` | MIT text |
| `SECURITY.md` | how to report vulnerabilities privately |
| `CONTRIBUTING.md` | how to contribute (tests required, Pint required) |
| `RELEASING.md` | the release checklist + automation docs |
| `.gitattributes` | which files ship in the Composer archive (exclude tests, CI) |
| `.github/workflows/` | CI matrix + release automation |
| `docs/` | real documentation with runnable examples |

---

## 9. How to make *better* packages

Everything in this repo was shaped by a specific philosophy. Here it is, as a checklist
you can apply to any package you write:

1. **Framework-free core, optional glue.** The valuable code shouldn't require Laravel.
   Integration is a thin layer on top.
2. **Immutable value objects.** If it *is* a value (date, money, id), make it immutable.
3. **Validate at the boundary.** The constructor refuses invalid dates. Bad data never
   enters the system.
4. **Program to interfaces.** Data sources, formatters, providers — let users swap them.
   Two implementations prove the seam works.
5. **Typed errors, never silent `false`.** Loud crashes beat quiet corruption.
6. **Keep dependencies tiny.** `require` = the minimum. Optional features → `suggest` +
   `class_exists` guards.
7. **Wide version support = a CI matrix.** Claim it in composer.json, *prove* it in CI.
8. **SemVer + tags + CHANGELOG.** Versioning is a promise; enforce it with tooling
   (`bin/check-release.php`).
9. **Tests that round-trip.** For a calendar: AD→BS→AD must return the same day. Find
   the *one* invariant your domain has and test it exhaustively.
10. **Document with real examples.** Every doc example in `docs/` was executed against
    the real package before publishing.
11. **Never silently change historical data.** A dataset correction is a documented,
    versioned event — CHANGELOG entry + major/minor bump.
12. **Honest support lifecycles.** When a PHP version goes EOL, update the matrix and
    say so in the docs. Supporting forever is how packages rot.

**The biggest lesson:** a package is not a pile of features — it's a *contract*. The
README promises behavior; the tests enforce it; the version number communicates change;
the docs teach it. Build the contract first, then the features.

---

## 10. Glossary

| Term | Plain meaning |
|---|---|
| **Class / object** | blueprint / a thing made from it |
| **Property / method** | data on an object / behavior on an object |
| **Constructor** | code that runs when an object is born |
| **Static** | belongs to the class, not an instance |
| **`$this`** | "this object right here" |
| **Namespace** | full address of a class; PSR-4 maps it to a file path |
| **Autoloading** | PHP finds class files automatically (via Composer) |
| **Interface** | a contract of method signatures |
| **Enum** | a fixed list of allowed values |
| **Exception** | a loud, catchable error |
| **Value object** | immutable object whose identity is its value |
| **Immutable** | cannot be changed after creation; ops return new objects |
| **Factory** | a method/class that builds objects |
| **Provider/Strategy** | swap an implementation behind an interface |
| **DI / container** | Laravel's way of building and handing out objects |
| **Facade** | static-looking shortcut to a container instance |
| **Singleton** | one shared instance for the whole app |
| **Macro** | add a method to an existing class without touching it |
| **Blade directive** | `@something(...)` sugar in views |
| **Cast** | automatic value translation between DB and objects |
| **Testbench** | a fake Laravel app for testing packages |
| **SemVer** | MAJOR.MINOR.PATCH versioning contract |
| **Tag** | an immutable named commit (release point) |
| **Packagist** | the public Composer registry |

---

## 11. Exercises

Do these in order. Each one uses the package you now understand.

1. **Read** `src/NepaliDate.php` and find: one static method, one instance method, one
   `readonly` property, one exception thrown. Say in one sentence what each does.
2. **Add a named constructor** `NepaliDate::fromEnglish(string $date)` that is just an
   alias for `fromAd()` (2 lines + a docblock). Run `composer test` — add one Pest test
   for it.
3. **Write a new config key** `'default_separator'` and make `toDateString()` use it via
   `Config::get(...)` with `'-'` as fallback. Publish-test by changing the value.
4. **Add a Blade directive** `@nepaliDateShort($value)` that renders `'Y-m-d'`, modeled
   on the existing `@nepaliDate`. Register it in the provider, test with Testbench.
5. **Extend the interface.** Add `yearData(int $year): array` to `CalendarDataProvider`,
   implement it in *both* providers, and use it somewhere in `Calendar`. Notice you never
   touched the conversion logic.
6. **Bump a version properly:** CHANGELOG entry → bump `VERSION` in the provider → run
   `php bin/check-release.php v1.15.0` → tag → push → watch CI run the matrix and the
   release workflow create the GitHub Release.
7. **Read the failure you caused:** in exercise 6, first push a tag whose `VERSION`
   doesn't match the tag name and watch `check-release.php` (in CI) explain the mismatch.
8. **Publish (final boss):** claim the Packagist name, connect the webhook, push a tag,
   and run `composer require sambat/nepali-calendar` in a scratch project to confirm the
   whole loop works end to end.

---

*You now know enough to read, modify, and eventually write packages like this one. The
next time you see `final class`, `readonly`, `interface`, or `ServiceProvider`, you know
exactly why they're there: contracts, safety, and seams.*
