<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use PdfGate\Internal\Release\ChangelogBuilder;

$releaseVersion = getenv('RELEASE_VERSION') ?: '';
$changelogPath = getenv('CHANGELOG_PATH') ?: 'CHANGELOG.md';
$releaseDate = getenv('RELEASE_DATE') ?: gmdate('Y-m-d');
$targetCommit = getenv('TARGET_COMMIT') ?: 'HEAD';
$dryRun = isTruthy(getenv('DRY_RUN') ?: '');

if ($releaseVersion === '') {
    fwrite(STDERR, "RELEASE_VERSION must be set to MAJOR.MINOR.PATCH or vMAJOR.MINOR.PATCH\n");
    exit(1);
}

if (!preg_match('/^v?\d+\.\d+\.\d+$/', $releaseVersion)) {
    fwrite(STDERR, sprintf(
        "Release version '%s' must match MAJOR.MINOR.PATCH or vMAJOR.MINOR.PATCH\n",
        $releaseVersion
    ));
    exit(1);
}

if (!is_file($changelogPath)) {
    fwrite(STDERR, sprintf("CHANGELOG file not found: %s\n", $changelogPath));
    exit(1);
}

ensureGitRepository();

$normalizedVersion = ltrim($releaseVersion, 'v');
$currentReleaseTag = 'v' . $normalizedVersion;
$previousReleaseTag = detectPreviousReleaseTag($targetCommit, $currentReleaseTag);
$range = $previousReleaseTag !== '' ? sprintf('%s..%s', $previousReleaseTag, $targetCommit) : $targetCommit;
$subjects = gitLines(sprintf('log --reverse --format=%%s %s', escapeshellarg($range)));

$builder = new ChangelogBuilder();
$releaseData = $builder->buildReleaseNotes($subjects, $previousReleaseTag !== '' ? $previousReleaseTag : null);
$updatedChangelog = $builder->updateChangelog(
    (string) file_get_contents($changelogPath),
    $normalizedVersion,
    $releaseDate,
    $releaseData['notes']
);

if ($dryRun) {
    fwrite(STDOUT, sprintf("Dry run: CHANGELOG.md would be updated for %s\n", $normalizedVersion));
    fwrite(STDOUT, sprintf("%s\n", $updatedChangelog));
    exit(0);
}

file_put_contents($changelogPath, $updatedChangelog);

function detectPreviousReleaseTag(string $targetCommit, string $currentReleaseTag): string
{
    $tags = gitLines(sprintf(
        "tag --merged %s --list 'v*.*.*' --sort=-v:refname",
        escapeshellarg($targetCommit)
    ), false);

    foreach ($tags as $tag) {
        if ($tag !== '' && $tag !== $currentReleaseTag) {
            return $tag;
        }
    }

    return '';
}

function ensureGitRepository(): void
{
    exec('git rev-parse --git-dir >/dev/null 2>&1', $output, $exitCode);

    if ($exitCode !== 0) {
        fwrite(STDERR, "prepare-release.php must be run inside a git repository\n");
        exit(1);
    }
}

/**
 * @return list<string>
 */
function gitLines(string $command, bool $failOnError = true): array
{
    exec('git ' . $command . ' 2>&1', $output, $exitCode);

    if ($exitCode !== 0 && $failOnError) {
        fwrite(STDERR, "Git command failed while preparing the release.\n");
        exit(1);
    }

    return normalizeSubjectLines(implode("\n", $output));
}

/**
 * @return list<string>
 */
function normalizeSubjectLines(string $contents): array
{
    $lines = preg_split("/\r\n|\n|\r/", $contents);

    if ($lines === false) {
        return array();
    }

    return array_values(array_filter($lines, static function (string $line): bool {
        return trim($line) !== '';
    }));
}

function isTruthy(string $value): bool
{
    $normalized = strtolower(trim($value));

    return in_array($normalized, array('1', 'true', 'yes', 'on'), true);
}
