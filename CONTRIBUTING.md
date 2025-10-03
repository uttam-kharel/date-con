# Contributing

Contributions are welcome and appreciated. This project is small but carefully
maintained - please read the guidelines before opening an issue or pull request.

## Code of conduct

Be kind and constructive. Harassment of any kind is not tolerated.

## Getting started

```bash
git clone https://github.com/sambat/nepali-calendar.git
cd nepali-calendar
composer install
composer test        # Pest suite
composer pint        # Laravel Pint code style
```

The test suite is the source of truth for calendar correctness - never ship a
change that breaks it. Calendar data changes especially must be justified by an
independent, verifiable source (government gazette, holiday calendars, ...).

## Development workflow

1. Fork the repository and create a feature branch:
   `git checkout -b feat/your-feature`.
2. Make your change with focused, meaningful commits (Conventional Commits style,
   e.g. `feat:`, `fix:`, `docs:`, `test:`, `chore:`).
3. Add or update tests for every behavior change.
4. Run the full suite and Pint before committing:
   ```bash
   vendor/bin/pest
   vendor/bin/pint --test
   vendor/bin/pint        # auto-fix if needed
   ```
5. Keep your branch up to date with `main` and open a pull request describing
   the *why* of the change.

## Project layout

| Path | Purpose |
|---|---|
| `src/Calendar.php` | The conversion engine (epoch-day math, provider-driven) |
| `src/NepaliDate.php` | The immutable value object (public API surface) |
| `src/Contracts/` | Extension points (e.g. `CalendarDataProvider`) |
| `src/Providers/` | Shipped data providers (array, database) |
| `src/Constants/CalendarData.php` | The verified BS 2000-2099 month table |
| `src/Support/` | Formatting, parsing, numeral and config helpers |
| `src/Commands/` | Artisan commands (`nepali:convert`, `nepali:info`, `nepali:seed`) |
| `tests/` | Pest suite (Unit + Feature) |
| `config/` | Publishable package config |
| `database/migrations/` | Migration for the database driver |

## Reporting bugs

Open an issue with:

- PHP version, Laravel version and package version
- A minimal reproduction snippet
- Expected vs. actual output

For date-conversion bugs, include the AD and BS dates you believe are correct and
the source you verified them against.

## Security

Found a security issue? Do **not** open a public issue - see `SECURITY.md`.

## License

By contributing you agree that your contributions are licensed under the MIT
License - see `LICENSE.md`.
