<?php

declare(strict_types=1);

/**
 * Release version-consistency checker.
 *
 * Verifies that a git tag and the release metadata agree before a GitHub
 * Release is created:
 *
 *     1. The tag looks like a semantic version (vX.Y.Z, optional -prerelease).
 *     2. The VERSION constant in src/NepaliCalendarServiceProvider.php matches
 *        the tag (exact, for v1.0.0+; for pre-1.0 milestones the constant may
 *        already point forward, e.g. v0.9.0 carried VERSION '1.0.0').
 *     3. CHANGELOG.md has a "## [X.Y.Z]" entry (required for v1.2.0+; older
 *        tags predate the changelog discipline and only warn).
 *
 * Usage:
 *     php bin/check-release.php            # uses GITHUB_REF_NAME or git describe
 *     php bin/check-release.php v1.10.1    # explicit tag
 *
 * Exit code is 0 when the release is consistent, 1 otherwise.
 */
$root = dirname(__DIR__);

$tag = $argv[1]
    ?? getenv('GITHUB_REF_NAME')
    ?: trim((string) shell_exec('git describe --tags --abbrev=0 2>/dev/null'));

if ($tag === '' || $tag === null) {
    fwrite(STDERR, "No tag given and none detected (set GITHUB_REF_NAME or pass vX.Y.Z).\n");

    exit(1);
}

$version = ltrim($tag, 'v');
$semver = '/^\d+\.\d+\.\d+(?:-[0-9A-Za-z.-]+)?$/';
$major = (int) explode('.', $version)[0];

$failures = [];
$warnings = [];

// 1. Semantic-version shape.
if (preg_match($semver, $version) !== 1) {
    $failures[] = "Tag \"{$tag}\" is not a semantic version (expected vX.Y.Z, optionally -prerelease).";
}

// 2. Provider VERSION constant.
$provider = is_file($root.'/src/NepaliCalendarServiceProvider.php')
    ? file_get_contents($root.'/src/NepaliCalendarServiceProvider.php')
    : false;
if ($provider === false) {
    $warnings[] = 'src/NepaliCalendarServiceProvider.php not found — skipping the version-constant check.';
} elseif (preg_match("/VERSION\s*=\s*'([^']+)'/", $provider, $m) !== 1) {
    $warnings[] = 'No VERSION constant found in NepaliCalendarServiceProvider — skipping.';
} else {
    $const = $m[1];

    if ($major >= 1 && $const !== $version) {
        $failures[] = "VERSION constant is \"{$const}\" but the tag says {$version}.";
    } elseif ($major < 1 && version_compare($const, $version, '<')) {
        $failures[] = "Pre-1.0 tag {$version} carries VERSION \"{$const}\" which is behind the tag.";
    } else {
        $status = $const === $version ? 'matches' : "{$const} (forward, pre-1.0 milestone)";
        echo "  ✓ VERSION constant {$status} the tag.\n";
    }
}

// 3. CHANGELOG entry.
$changelog = is_file($root.'/CHANGELOG.md')
    ? file_get_contents($root.'/CHANGELOG.md')
    : false;
if ($changelog === false) {
    $warnings[] = 'CHANGELOG.md not found — skipping the changelog check.';
} elseif (preg_match('/^## \\['.preg_quote($version, '/').'\\]/m', $changelog) === 1) {
    echo "  ✓ CHANGELOG has a [{$version}] entry.\n";
} elseif ($major >= 2 || $version === '1.2.0' || version_compare($version, '1.2.0', '>')) {
    $failures[] = "CHANGELOG.md has no \"## [{$version}]\" entry.";
} else {
    $warnings[] = "No CHANGELOG entry for {$version} (predates the changelog discipline).";
}

foreach ($warnings as $w) {
    echo "  ! {$w}\n";
}

if ($failures !== []) {
    foreach ($failures as $f) {
        fwrite(STDERR, "  ✗ {$f}\n");
    }
    fwrite(STDERR, "Release check FAILED for {$tag}.\n");

    exit(1);
}

echo "Release check PASSED for {$tag}.\n";

exit(0);
