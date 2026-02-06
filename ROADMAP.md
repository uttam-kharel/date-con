# Roadmap — sambat/nepali-calendar

A living, version-by-version plan for the Nepali (Bikram Sambat) date & time
package. It starts from what is **already shipped and tagged**, then plans
backward-compatible features, the PHP-lifecycle-driven majors, and the engineering
that must happen *between* releases.

> Status markers: ✅ shipped and tagged · 🎯 planned

---

## 1. Where we are today (v1.2.0)

| Version | PHP | What shipped | Status |
|---|---|---|---|
| **v1.0.0** | ^8.1 | O(1) AD⇄BS engine, immutable `NepaliDate`, Devanagari numerals, full `date()` formatting with Nepali/Roman/English names, arithmetic, diffs, periods, calendar grids, validation rules, Blade directives, Carbon macros, facade, artisan commands, helpers — 81 tests | ✅ tagged |
| **v1.1.0** | ^8.1 | Configurable data source: `driver` option (`algorithm` \| `database`), `CalendarDataProvider` contract, database driver, publishable migration, `nepali:seed`, runtime provider swapping, plain-PHP PDO support — 88 tests | ✅ tagged |
| **v1.2.0** | ^8.1–8.5 | Explicit PHP 8.1–8.5 support, GitHub Actions matrix, MIT license, CHANGELOG, CONTRIBUTING, SECURITY, `.gitattributes`, README badges | ✅ tagged |

Current baseline: **31 commits, 3 annotated tags, 88 tests / 2,429 assertions,
Pint clean, `composer validate --strict` green, every tag verified standalone.**

The conversion range is **BS 2000–2099 / AD 1943–2043**, bounded by the
observation-verified month table. That dataset — and its verification — is the
foundation everything else stands on (see §7).

---

## 2. Vision

> A complete, strongly typed, dependency-light **Bikram Sambat date & calendar
> ecosystem for PHP** — not just another AD⇄BS converter.

**Design principles**

- Framework-agnostic core, Laravel as an optional layer
- Immutable by default, strong typing, no unnecessary dependencies
- Accurate, *governed* BS dataset — the engine converts, the dataset is the source of truth
- Backward compatibility via SemVer + a real deprecation policy
- i18n-ready (Devanagari numerals, Nepali/Roman/English names, locale infrastructure)
- Timezone-aware but calendar-system ≠ timezone (BS math is timezone-free; instants are not)
- Documented, tested, benchmarked, CI-verified at every release

**Target architecture**

```
                 nepali-calendar
                       │
          ┌────────────┴────────────┐
          │                         │
       Core (PHP)               Adapters
          │                         │
  ┌───────┼────────┐      ┌─────────┼──────────┐
  │       │        │      │         │          │
Date  Calendar  Locale   Laravel  Carbon   Livewire/Filament
  │       │        │
  └───────┼────────┘
          │
       Dataset            ← governed, versioned, verified BS calendar data
```

---

## 3. PHP support strategy

Follow the PHP lifecycle, not nostalgia. PHP 8.1 is already EOL
(security support ended Dec 2025) — it stays as the **legacy baseline for the 1.x
line only**, and each major raises the floor deliberately:

| Package major | PHP floor | Why | CI matrix |
|---|---|---|---|
| 1.x (current) | ^8.1 | Compatibility baseline; 8.1 already EOL, so no new features are *built on* it | 8.1 – 8.5 |
| 2.x | ^8.2 | 8.2 security until **Dec 2026**; enables readonly classes, first-class callables, DNF types | 8.2 – 8.5 |
| 3.x | ^8.3 | 8.3 security until **Dec 2027**; typed class constants | 8.3 – 8.5 |
| 4.x | ^8.4 / ^8.5 | 8.4 until **Dec 2028**, 8.5 until **Dec 2029**; adopt `clone with`, `#[\NoDiscard]` where they genuinely improve the public API | current stable + beta |

Rule: a major version never promises support beyond ~1–2 years of its target's
EOL. PHP 8.6 (beta as of late 2026) becomes a CI target when 2.x+ lands.

---

## 4. Version roadmap

### 4.1 Shipped (see §1)

### 4.2 Planned — 1.x · backward-compatible features (PHP 8.1 baseline kept)

| Version | 🎯 Features | Effort |
|---|---|---|
| **v1.3.0** | **Fiscal year & quarters & date ranges** — `NepaliFiscalYear` (`fromDate`, `label` `2083/84`, `startDate` = Shrawan 1, `endDate` = Ashadh 31, `quarter`, `contains`), fiscal-quarter helpers, `NepaliDateRange::between()` with `days()/weeks()/months()/years()` iteration and `count()` | M |
| **v1.4.0** | **Holidays & business days** — `HolidayProvider` contract (optional, never hardcoded in core), `isHoliday()`, `isBusinessDay()`, `addBusinessDays()`, `businessDaysUntil()`, Nepal weekend config (Saturday) | M |
| **v1.5.0** | **Laravel data layer** — Eloquent casts `NepaliDateCast` / `NepaliDateTimeCast` (store canonical AD, present BS), validation expansion (`nepali_date_before/after/between`, ranged format rule), query helpers (`whereNepaliYear`, `whereNepaliMonth` on AD-backed columns) | M |
| **v1.6.0** | **Formatting & DX polish** — format presets (`DATE_SHORT/MEDIUM/LONG/FULL`, datetime variants), locale-aware number grouping, richer `@method` docblocks for IDE autocomplete | S |
| **v1.7.0** | **Livewire pickers** — `NepaliDatePicker`, `NepaliMonthPicker`, `NepaliYearPicker` as a separate `nepali-calendar-livewire` package | L |
| **v1.8.0** | **Filament pickers** — `NepaliDatePicker` etc. as a separate `nepali-calendar-filament` package | L |
| **v1.9.0** | **Export** — `toArray()/toJson()` already exist; add CSV and iCalendar (`.ics`) export for calendars and ranges | S |

### 4.3 Planned — 2.x · architecture release (PHP 8.2+, breaking changes land here, not randomly in 1.x)

| Version | 🎯 Features |
|---|---|
| **v2.0.0** | **Dataset extraction** — move the BS month table out of `CalendarData` into governed `data/calendar/*.json` files with a `JsonCalendarDataProvider` (plugs into the existing provider contract; engine and dataset fully decoupled). **Contracts pass** — `DateInterface`, `CalendarInterface`, `ConverterInterface`, `FormatterInterface`, `LocaleInterface`, `DatasetInterface`. **Immutable-first** — readonly properties, `NepaliDateImmutable` as the canonical type. **Quality** — PHPStan max level, exception hierarchy review, `illuminate/*` moved to optional/suggested where possible |
| **v2.1.0** | Advanced formatting — presets engine, quoted literals everywhere, locale-aware `%d`/`%f` style numerics |
| **v2.2.0** | Localization infrastructure — locale objects (`ne`, `en`; `hi`, `new` only if properly maintained), translations as datasets, `$date->locale('ne')` |
| **v2.3.0** | Holiday providers — `HolidayProviderInterface`, `NepalHolidayProvider` (dataset-driven), `DatabaseHolidayProvider` for app-owned holidays |
| **v2.4.0** | Performance — benchmark suite (1 / 1k / 100k conversions), in-memory dataset cache, static analysis of hot paths |
| **v2.5.0** | Recurrence engine — daily/weekly/monthly/yearly rules with hard guardrails against runaway iteration |
| **v2.6.0** | Laravel query API — `whereNepaliDate`, `whereNepaliYear`, `whereNepaliMonth` scopes over canonical AD columns |

### 4.4 Planned — 3.x · ecosystem (PHP 8.3+)

| Version | 🎯 Features |
|---|---|
| **v3.0.0** | **Package split** — `nepali-calendar` (core, zero framework deps), `nepali-calendar-laravel` (provider, casts, validation, Blade, commands, helpers), `nepali-calendar-carbon` (adapter). Core stays tiny; integrations version independently |
| **v3.1.0** | CLI application — `nepali-calendar today`, `nepali-calendar convert 2026-08-14`, `nepali-calendar calendar 2083` |
| **v3.2.0** | REST API package + **documentation site** (installation, quick start, dates, datetime, conversion, formatting, localization, calendar, fiscal year, holidays, Laravel, Carbon, migration, FAQ) |
| **v3.3.0** | `nepali-calendar-holidays` — maintained Nepal public-holiday dataset as its own versioned package |

### 4.5 Future — 4.x (modern PHP 8.4 / 8.5+)

Raise the floor per the lifecycle table. Adopt genuinely useful modern syntax
(`clone with`, `#[\NoDiscard]`, pipe operator in *internal* composition) **without**
making public APIs cryptic. Only do this when the ecosystem has moved — not for the
sake of new keywords.

---

## 5. The one technical risk that gates everything

**Dataset expansion beyond BS 2099.** Every feature above is built on the month
table; the table's real-world source currently runs to 2099. Before promising
wider ranges:

1. Source and *document* an authoritative continuation (gazette, government
   calendar, cross-checked holiday calendars).
2. Verify against independent anchors (Nepali New Year dates, Poush 1, festivals).
3. Extend `data/calendar/*.json` + fixtures + round-trip tests.
4. Release as a minor (backward compatible) with an explicit CHANGELOG note.

Until then, the **supported range is a documented contract**, and out-of-range
dates throw typed exceptions — never silent `null`s.

---

## 6. Engineering tracks (continuous, between releases)

### 6.1 Dataset governance

- The dataset is the source of truth; the engine only converts.
- Every dataset change: source verification → review → automated validation →
  test fixtures → release.
- **Never silently modify historical calendar data.** Corrections require an
  explicit CHANGELOG entry (e.g. `Fixed BS 2083 month-length dataset.`).

### 6.2 Testing strategy

- **Unit** — conversion fixtures (AD→BS, BS→AD), full-range round-trips
  (AD→BS→AD and BS→AD→BS), year/month/day boundaries, leap years, invalid dates
  (month 0/13, day 0, day > month length), formatting, numerals, parsing.
- **Integration** — Laravel (provider, casts, validation, Blade, commands),
  Carbon adapters, plain-PHP/PDO database driver.
- **Performance** — conversion, creation, formatting, dataset lookup, calendar
  generation, range iteration; memory + cache-hit/miss.

### 6.3 Quality gates (every PR, and before every tag)

Pest/PHPUnit ✅ · PHPStan ✅ · Pint ✅ · `composer validate` ✅ · `composer audit` ✅
· dataset validation ✅ · coverage (target 95%+ at v1.0.0 of each major) · docs
check ✅

A release does not happen if any gate fails.

### 6.4 CI evolution

- Now: matrix PHP 8.1–8.5, `--prefer-lowest` on the floor, Pint, validate, audit.
- Later: `lowest / current / latest` dependency legs; coverage job; a **release
  workflow** that bumps version, updates CHANGELOG, tags `vX.Y.Z`, and publishes
  to Packagist.

### 6.5 Documentation

Every feature documents: what it does · installation · basic example · advanced
example · exceptions · edge cases · Laravel usage. Advanced material lives in
`docs/`; the README stays a short example + pointers.

### 6.6 Release discipline

- SemVer strictly. **Patch** = fixes · **Minor** = backward-compatible features ·
  **Major** = breaking API or PHP floor.
- Annotated tags only, exact names (`v1.3.0`, `v2.0.0-beta.1`, `v2.0.0-rc.1`).
  Never `latest`, `final`, `stable-final`.
- CHANGELOG categories: Added / Changed / Deprecated / Removed / Fixed / Security.
- **Deprecation policy**: mark deprecated in `N`, emit warnings in `N+1`, remove
  in the next major.

### 6.7 Git workflow

`main` + `feature/*` / `fix/*`, Conventional Commits (`feat:`, `fix:`, `docs:`,
`test:`, `ci:`, `chore:`, `refactor:`), releases straight from `main`. The
history stays stepwise and readable — one feature, one commit, tests green at
every tag.

---

## 7. Timeline (approximate)

| When | Milestones |
|---|---|
| 2026 Q3–Q4 | v1.3.0 (fiscal year, quarters, ranges) · v1.4.0 (holidays, business days) |
| 2027 H1 | v1.5.0 (casts, validation expansion, query helpers) · v1.6.0 (presets, DX) |
| 2027 H2 | v1.7.0–v1.9.0 (Livewire, Filament, export) · **v2.0.0** (PHP 8.2+, JSON dataset, contracts) |
| 2028 | v2.1–v2.6 (formatting, locales, holidays, performance, recurrence, query API) |
| 2028–2029 | v3.0 ecosystem split · CLI · API · docs site · holidays package |
| 2029+ | v4.0 — modern PHP floor |

The roadmap is a living document: it is revisited and updated at every release,
and reality wins over dates.

---

## 8. Explicitly out of scope (core)

- HTTP, UI, database, Redis, cache adapters inside the core package
- Hardcoded holiday/event data in core (providers and datasets only)
- Mutable core objects (immutable-first)
- Supporting EOL PHP in new majors
- Claiming locales that aren't properly maintained (`hi`, `new`, …)

Ecosystem features live in **adapters** — core stays `composer require
nepali-calendar/nepali-calendar`-light.
