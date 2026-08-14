# Releasing

This project follows **Semantic Versioning** (see [CHANGELOG.md](CHANGELOG.md)) and
releases directly from `main`. A release is an annotated git tag (`vX.Y.Z`); the
`release` GitHub Actions workflow (`.github/workflows/release.yml`) runs the test
suite on the tag and creates a GitHub Release with auto-generated notes.

## Deciding the version

| Change | Version bump |
|---|---|
| Bug fix / internal change | `PATCH` → `1.7.1` |
| New backward-compatible feature | `MINOR` → `1.8.0` |
| Breaking API change or PHP floor raise | `MAJOR` → `2.0.0` |

Pre-releases use the exact tag names `v2.0.0-beta.1`, `v2.0.0-rc.1` — never
`latest`, `final` or `stable-final`.

## Cut a release (10 minutes)

1. **Update the changelog.** Move the unreleased entries into a new
   `## [X.Y.Z] - YYYY-MM-DD` section under [Unreleased] using the
   Added / Changed / Deprecated / Removed / Fixed / Security categories.

2. **Bump the version constant** in `src/NepaliCalendarServiceProvider.php`
   (`public const VERSION = 'X.Y.Z';`) so `php artisan about` reports it.

3. **Update the docs that mention version numbers and test counts** — README,
   ROADMAP "Where we are today", and this file if the process changed.

4. **Verify everything:**
   ```bash
   vendor/bin/pest
   vendor/bin/pint --test
   composer validate --strict
   composer audit
   ```

5. **Commit and tag** (Conventional Commits, straight on `main`):
   ```bash
   git add -A
   git commit -m "chore: bump version to X.Y.Z"
   git tag -a vX.Y.Z -m "vX.Y.Z — Short release summary"
   ```

6. **Push** the branch and the tag. The `release` workflow then runs the suite
   on the tag and publishes the GitHub Release:
   ```bash
   git push origin main
   git push origin vX.Y.Z
   ```

7. **Publish to Packagist** (done once per release): Packagist is hooked to the
   repository, so pushing the tag is enough to publish `sambat/nepali-calendar`.
   Verify on [packagist.org](https://packagist.org) that the new version shows
   up, then update the GitHub Release description with the CHANGELOG summary if
   the auto-generated notes are not enough.

## Dataset changes (read before touching CalendarData)

Calendar data is the package's source of truth and is **governed**:

- Never modify historical month lengths without an independent, verifiable
  source (government gazette, Panchanga Samiti publications, cross-checked
  holiday calendars) and an explicit CHANGELOG entry
  (e.g. `Fixed BS 2083 month-length dataset.`).
- Extending the range (e.g. adding BS 2101) follows the same process as the
  v1.7.0 BS 2100 extension: source → cross-check anchors → fixtures →
  round-trip tests → backward-compatible minor release with a CHANGELOG note.
- Update the out-of-range exception fallback text in
  `src/Exceptions/NepaliDateOutOfRangeException.php` only if the dataset grows
  again — the labels are derived from the active data.
