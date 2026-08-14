# Releasing

Everything about shipping a release of `sambat/nepali-calendar`: how versions
are decided, what the automated checks verify, the exact step-by-step
checklist, what CI does on a tag, the release/tag history, and how to fix a
bad release.

## What a release is

- A release is an **annotated git tag** (`vX.Y.Z`) on `main`.
- Pushing a tag triggers **two** GitHub Actions workflows:

  | Workflow | What it does on a tag push |
  |---|---|
  | `tests` (`.github/workflows/tests.yml`) | runs the full **Laravel 11–13 / PHP 8.2–8.5 matrix** against the tagged code |
  | `release` (`.github/workflows/release.yml`) | **checks the version is consistent**, validates `composer.json`, runs the suite + Pint, then creates the GitHub Release with notes |

- The package is **not yet published to Packagist** — that is one manual step
  per release (see [Publish to Packagist](#publish-to-packagist)).

## Deciding the version (SemVer)

| Change | Version bump | Example |
|---|---|---|
| Bug fix / internal change | `PATCH` | `1.10.1` |
| New backward-compatible feature | `MINOR` | `1.11.0` |
| Breaking API change or PHP floor raise | `MAJOR` | `2.0.0` |

Pre-releases use the exact tag names `v2.0.0-beta.1`, `v2.0.0-rc.1` — never
`latest`, `final`, or `stable-final`. PHP version floors move only in a MAJOR
release and follow the PHP support lifecycle (8.1 EOL, 8.2 supported through
Dec 2026, …).

## What the version check verifies

`php bin/check-release.php vX.Y.Z` — also run automatically as the first step
of the `release` workflow — fails the release unless:

1. the tag is a semantic version (`vX.Y.Z`, optional `-prerelease`);
2. `NepaliCalendarServiceProvider::VERSION` matches the tag for `v1.0.0+`
   (pre-1.0 milestone tags may carry a forward constant — `v0.9.0` had
   `VERSION = '1.0.0'`);
3. `CHANGELOG.md` has a `## [X.Y.Z]` entry (required for `v1.2.0+`; older
   tags predate the changelog discipline and only warn).

Example output:

```
$ php bin/check-release.php v1.10.1
  ✓ VERSION constant matches the tag.
  ✓ CHANGELOG has a [1.10.1] entry.
Release check PASSED for v1.10.1.
```

If a check fails the exact mismatch is printed and the workflow stops before
any release is created. You can also run the checker against **every existing
tag** in one pass (see [Auditing tags](#auditing-tags)).

## Cut a release (checklist)

1. **Decide the version** with the SemVer table above.

2. **Update the changelog.** Move the `[Unreleased]` entries into a new
   `## [X.Y.Z] - YYYY-MM-DD` section using the Added / Changed / Deprecated /
   Removed / Fixed / Security categories.

3. **Bump the version constant** in `src/NepaliCalendarServiceProvider.php`
   (`public const VERSION = 'X.Y.Z';`) so `php artisan about` and the version
   check agree with the tag.

4. **Update docs that state version numbers and test counts** — README,
   ROADMAP "Where we are today", and this file if the process changed.

5. **Verify locally** — everything the CI will check, before tagging:

   ```bash
   vendor/bin/pest                 # 151+ tests, 2700+ assertions
   vendor/bin/pint --test          # code style
   composer validate --strict --no-check-publish
   composer audit                  # security advisories
   php bin/check-release.php vX.Y.Z
   ```

6. **Commit and tag** (Conventional Commits, straight on `main`):

   ```bash
   git add -A
   git commit -m "chore: bump version to X.Y.Z"
   git tag -a vX.Y.Z -m "vX.Y.Z — Short release summary"
   ```

7. **Push** — the `tests` matrix and the `release` workflow both start on the
   tag:

   ```bash
   git push origin main
   git push origin vX.Y.Z
   ```

8. **Watch the runs** on the Actions tab. Both must be green:
   - `tests`: 4 jobs (Laravel 11 on PHP 8.2, 12 on 8.3, 13 on 8.4 and 8.5);
   - `release`: version check → composer validate → tests → Pint → GitHub Release.

9. **Verify the GitHub Release** on the repo's Releases page: it exists, is
   not a draft, and the notes cover the changes. If the auto-generated notes
   are thin, paste the CHANGELOG section into the release description.

10. **Publish to Packagist** (see below).

## What CI runs per event

| Event | `tests` workflow | `release` workflow |
|---|---|---|
| push to `main` / `master` | full matrix | — |
| pull request | full matrix | — |
| push a `v*` tag | full matrix **on the tag** | version check + suite + release |
| manual dispatch (Actions tab → `release` → Run workflow) | — | version check + suite + release for the tag given in the `tag` input |

The `release` workflow therefore re-runs the entire suite on the exact commit
being released and refuses to create a release for a tag whose version is
inconsistent.

## Auditing tags

Check every tag at once (for example after backfilling history or before a
milestone):

```bash
for t in $(git tag); do
  git archive "$t" | tar -x -C "/tmp/audit-$t"
  cp bin/check-release.php "/tmp/audit-$t/bin/check-release.php"
  php "/tmp/audit-$t/bin/check-release.php" "$t"
done
```

All 15 tags pass the current rules:

| Tag | Tagged | Provider `VERSION` | CHANGELOG entry | GitHub Release |
|---|---|---|---|---|
| v0.1.0 | 2021-01-15 | — (pre-const) | — (pre-changelog) | ❌ not created |
| v0.5.0 | 2022-04-02 | — (pre-const) | — (pre-changelog) | ❌ not created |
| v0.9.0 | 2023-09-08 | `1.0.0` (forward) | — (pre-changelog) | ❌ not created |
| v1.0.0 | 2024-06-21 | `1.0.0` ✓ | — (pre-changelog) | ❌ not created |
| v1.1.0 | 2025-06-27 | `1.1.0` ✓ | — (pre-changelog) | ❌ not created |
| v1.2.0 | 2026-01-09 | `1.2.0` ✓ | ✓ | ❌ not created |
| v1.3.0 | 2026-05-15 | `1.3.0` ✓ | ✓ | ❌ not created |
| v1.4.0 | 2026-07-06 | `1.4.0` ✓ | ✓ | ❌ not created |
| v1.5.0 | 2026-08-07 | `1.5.0` ✓ | ✓ | ❌ not created |
| v1.6.0 | 2026-08-14 | `1.6.0` ✓ | ✓ | ❌ not created |
| v1.7.0 | 2026-08-14 | `1.7.0` ✓ | ✓ | ❌ not created |
| v1.8.0 | 2026-08-14 | `1.8.0` ✓ | ✓ | ❌ not created |
| v1.9.0 | 2026-08-14 | `1.9.0` ✓ | ✓ | ❌ not created |
| v1.10.0 | 2026-08-14 | `1.10.0` ✓ | ✓ | ✅ created |
| v1.10.1 | 2026-08-14 | `1.10.1` ✓ | ✓ | ✅ created |

**Backfilling GitHub Releases** for the 13 older tags: the release workflow
did not exist at their commits, so pushing them again does not create runs.
Backfill either from the web UI (Releases → *Draft a new release* → choose
tag, paste the CHANGELOG section) or via the API with a token:

```bash
for t in v0.1.0 v0.5.0 v0.9.0 v1.0.0 v1.1.0 v1.2.0 v1.3.0 v1.4.0 v1.5.0 \
         v1.6.0 v1.7.0 v1.8.0 v1.9.0; do
  gh release create "$t" --repo uttam-kharel/date-con --generate-notes
done
```

## Publish to Packagist

The package is **not on Packagist yet** — `composer require
sambat/nepali-calendar` currently resolves from the GitHub repository, not
Packagist. To publish:

1. Claim the name: log in at [packagist.org](https://packagist.org) →
   *Submit* → `https://github.com/uttam-kharel/date-con` (the package name in
   `composer.json` is `sambat/nepali-calendar`).
2. Connect the webhook/Packagist token so **every pushed `v*` tag publishes
   automatically**.
3. After the first release, add the Packagist badge (`packagist/v` and
   `packagist/dt`) to the README and this checklist.

## Fixing a bad release

- **Tag points at the wrong commit or wrong version** — delete and re-create
  it, then push:

  ```bash
  git tag -d vX.Y.Z
  git push origin :refs/tags/vX.Y.Z        # deletes the remote tag
  git tag -a vX.Y.Z -m "vX.Y.Z — Short release summary"   # at the right commit
  git push origin vX.Y.Z
  ```

  If a GitHub Release already exists for that tag, delete it first (web UI or
  `gh release delete vX.Y.Z`), then re-push.

- **A released version has a serious bug** — cut a `PATCH` immediately
  (checklist above). Do not reuse the broken tag.

- **A version must be uninstallable** — on Packagist use *Disable*/*abandon*
  for the version; keep the git tag (removing tags breaks clones and
  `composer update` for everyone who already requires it).

## Branching policy

- `main` is the only long-lived branch; releases are tagged straight from it.
- Conventional Commits everywhere (`feat:`, `fix:`, `docs:`, `ci:`, `chore:`,
  `test:`, `refactor:`, `perf:`).
- Everything that ships must be covered by tests and pass Pint; the CI matrix
  is the gate.

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
