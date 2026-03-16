<?php

declare(strict_types=1);

namespace PdfGate\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class ReleaseWorkflowScriptTest extends TestCase
{
    public function testProductionReleasePreparationAcceptsSemverTagAndExtractsReleaseNotes(): void
    {
        $changelogPath = $this->createChangelog(
            <<<MD
            ## [Unreleased]

            - Upcoming change.

            ## [1.2.3] - 2026-03-16

            - Stable release note.
            MD
        );

        $releaseNotesPath = tempnam(sys_get_temp_dir(), 'release-notes-');
        $githubOutputPath = tempnam(sys_get_temp_dir(), 'github-output-');

        $result = $this->runScript(array(
            'EVENT_NAME' => 'push',
            'GITHUB_REF_NAME' => 'v1.2.3',
            'CHANGELOG_PATH' => $changelogPath,
            'RELEASE_NOTES_PATH' => $releaseNotesPath,
            'GITHUB_OUTPUT' => $githubOutputPath,
        ));

        self::assertSame(0, $result['exitCode'], $result['stderr']);
        self::assertStringContainsString('release_tag=v1.2.3', $result['githubOutput']);
        self::assertStringContainsString('release_name=v1.2.3', $result['githubOutput']);
        self::assertStringContainsString('prerelease=false', $result['githubOutput']);
        self::assertStringContainsString('Stable release note.', $result['releaseNotes']);
        self::assertStringNotContainsString('Upcoming change.', $result['releaseNotes']);
    }

    public function testProductionReleasePreparationFailsWhenChangelogEntryIsMissing(): void
    {
        $changelogPath = $this->createChangelog(
            <<<MD
            ## [Unreleased]

            - Upcoming change.
            MD
        );

        $releaseNotesPath = tempnam(sys_get_temp_dir(), 'release-notes-');
        $githubOutputPath = tempnam(sys_get_temp_dir(), 'github-output-');

        $result = $this->runScript(array(
            'EVENT_NAME' => 'push',
            'GITHUB_REF_NAME' => 'v1.2.3',
            'CHANGELOG_PATH' => $changelogPath,
            'RELEASE_NOTES_PATH' => $releaseNotesPath,
            'GITHUB_OUTPUT' => $githubOutputPath,
        ));

        self::assertNotSame(0, $result['exitCode']);
        self::assertStringContainsString('CHANGELOG.md must include heading: ## [1.2.3] - YYYY-MM-DD', $result['stderr']);
    }

    public function testManualReleasePreparationUsesUnreleasedNotesAndMarksPrerelease(): void
    {
        $changelogPath = $this->createChangelog(
            <<<MD
            ## [Unreleased]

            - Test release note.

            ## [1.2.3] - 2026-03-16

            - Stable release note.
            MD
        );

        $releaseNotesPath = tempnam(sys_get_temp_dir(), 'release-notes-');
        $githubOutputPath = tempnam(sys_get_temp_dir(), 'github-output-');

        $result = $this->runScript(array(
            'EVENT_NAME' => 'workflow_dispatch',
            'RELEASE_TEST_TAG' => 'test-2026-03-16-1',
            'RELEASE_MODE' => 'prerelease',
            'CHANGELOG_PATH' => $changelogPath,
            'RELEASE_NOTES_PATH' => $releaseNotesPath,
            'GITHUB_OUTPUT' => $githubOutputPath,
        ));

        self::assertSame(0, $result['exitCode'], $result['stderr']);
        self::assertStringContainsString('release_tag=test-2026-03-16-1', $result['githubOutput']);
        self::assertStringContainsString('prerelease=true', $result['githubOutput']);
        self::assertStringContainsString('Test release note.', $result['releaseNotes']);
        self::assertStringNotContainsString('Stable release note.', $result['releaseNotes']);
    }

    public function testManualReleasePreparationRejectsNonTestNamespaceTags(): void
    {
        $changelogPath = $this->createChangelog(
            <<<MD
            ## [Unreleased]

            - Test release note.
            MD
        );

        $releaseNotesPath = tempnam(sys_get_temp_dir(), 'release-notes-');
        $githubOutputPath = tempnam(sys_get_temp_dir(), 'github-output-');

        $result = $this->runScript(array(
            'EVENT_NAME' => 'workflow_dispatch',
            'RELEASE_TEST_TAG' => 'v1.2.3-rc.1',
            'RELEASE_MODE' => 'prerelease',
            'CHANGELOG_PATH' => $changelogPath,
            'RELEASE_NOTES_PATH' => $releaseNotesPath,
            'GITHUB_OUTPUT' => $githubOutputPath,
        ));

        self::assertNotSame(0, $result['exitCode']);
        self::assertStringContainsString("Manual test tag 'v1.2.3-rc.1' must match test-<identifier>", $result['stderr']);
    }

    /**
     * @return array{exitCode:int,stderr:string,githubOutput:string,releaseNotes:string}
     */
    private function runScript(array $env): array
    {
        $command = array('bash', __DIR__ . '/../../scripts/prepare-release.sh');
        $descriptors = array(
            0 => array('pipe', 'r'),
            1 => array('pipe', 'w'),
            2 => array('pipe', 'w'),
        );

        $process = proc_open($command, $descriptors, $pipes, dirname(__DIR__, 2), array_merge($_ENV, $env));
        self::assertIsResource($process);

        fclose($pipes[0]);
        stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);

        return array(
            'exitCode' => $exitCode,
            'stderr' => $stderr,
            'githubOutput' => file_exists($env['GITHUB_OUTPUT']) ? (string) file_get_contents($env['GITHUB_OUTPUT']) : '',
            'releaseNotes' => file_exists($env['RELEASE_NOTES_PATH']) ? (string) file_get_contents($env['RELEASE_NOTES_PATH']) : '',
        );
    }

    private function createChangelog(string $contents): string
    {
        $path = tempnam(sys_get_temp_dir(), 'changelog-');
        self::assertNotFalse($path);
        file_put_contents($path, $contents);

        return $path;
    }
}
