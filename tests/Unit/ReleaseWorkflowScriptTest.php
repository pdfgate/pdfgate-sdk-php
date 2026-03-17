<?php

declare(strict_types=1);

namespace PdfGate\Tests\Unit;

use PdfGate\Internal\Release\ChangelogBuilder;
use PHPUnit\Framework\TestCase;

final class ReleaseWorkflowScriptTest extends TestCase
{
    public function testBuildReleaseNotesGroupsCommitSubjectsIntoSections(): void
    {
        $builder = new ChangelogBuilder();

        $result = $builder->buildReleaseNotes(
            array(
                'feat(api): add upload endpoint',
                'docs(readme): describe upload endpoint',
                'fix(client): handle timeout response',
                'refactor(http): simplify request builder',
                'test(client): add upload coverage',
                'chore(ci): tighten workflow checks',
                'misc subject without prefix',
            ),
            'v0.1.0'
        );

        self::assertStringContainsString("### Added\n\n- add upload endpoint", $result['notes']);
        self::assertStringContainsString("### Fixed\n\n- handle timeout response", $result['notes']);
        self::assertStringContainsString("### Changed\n\n- simplify request builder\n- misc subject without prefix", $result['notes']);
        self::assertStringContainsString("### Documentation\n\n- describe upload endpoint", $result['notes']);
        self::assertStringContainsString("### Tests\n\n- add upload coverage", $result['notes']);
        self::assertStringContainsString("### Maintenance\n\n- tighten workflow checks", $result['notes']);
    }

    public function testBuildReleaseNotesAddsFallbackMessageWhenThereAreNoSubjects(): void
    {
        $builder = new ChangelogBuilder();

        $result = $builder->buildReleaseNotes(array(), 'v0.1.0');

        self::assertSame("### Changed\n\n- No changes since v0.1.0.", $result['notes']);
    }

    public function testUpdateChangelogInsertsNewVersionBelowUnreleased(): void
    {
        $builder = new ChangelogBuilder();
        $updated = $builder->updateChangelog(
            <<<MD
            # Changelog

            All notable changes to this project are documented in this file.

            ## [Unreleased]

            ## [0.1.0] - 2026-03-05

            ### Added

            - Initial public SDK release.
            MD,
            '1.2.3',
            '2026-03-16',
            "### Added\n\n- add upload endpoint"
        );

        self::assertStringContainsString("## [Unreleased]\n\n## [1.2.3] - 2026-03-16", $updated);
        self::assertStringContainsString("### Added\n\n- add upload endpoint", $updated);
        self::assertStringContainsString('## [0.1.0] - 2026-03-05', $updated);
    }

    public function testPrepareReleaseCliRequiresReleaseVersion(): void
    {
        $workspace = sys_get_temp_dir() . '/pdfgate-release-cli-' . bin2hex(random_bytes(8));
        mkdir($workspace, 0777, true);

        $changelogPath = $workspace . '/CHANGELOG.md';

        file_put_contents(
            $changelogPath,
            <<<MD
            # Changelog

            All notable changes to this project are documented in this file.

            ## [Unreleased]

            ## [0.1.0] - 2026-03-05

            ### Added

            - Initial public SDK release.
            MD
        );

        $result = $this->runCli($workspace, array(
            'CHANGELOG_PATH' => $changelogPath,
        ));

        self::assertNotSame(0, $result['exitCode']);
        self::assertStringContainsString(
            'RELEASE_VERSION must be set to MAJOR.MINOR.PATCH or vMAJOR.MINOR.PATCH',
            $result['stderr']
        );
    }

    public function testPrepareReleaseCliRejectsInvalidReleaseVersion(): void
    {
        $workspace = sys_get_temp_dir() . '/pdfgate-release-cli-' . bin2hex(random_bytes(8));
        mkdir($workspace, 0777, true);

        $changelogPath = $workspace . '/CHANGELOG.md';

        file_put_contents(
            $changelogPath,
            <<<MD
            # Changelog

            All notable changes to this project are documented in this file.

            ## [Unreleased]
            MD
        );

        $result = $this->runCli($workspace, array(
            'RELEASE_VERSION' => 'test-2026-03-16-1',
            'CHANGELOG_PATH' => $changelogPath,
        ));

        self::assertNotSame(0, $result['exitCode']);
        self::assertStringContainsString(
            "Release version 'test-2026-03-16-1' must match MAJOR.MINOR.PATCH or vMAJOR.MINOR.PATCH",
            $result['stderr']
        );
    }

    /**
     * @param array<string, string> $env
     * @return array{exitCode:int,stderr:string,stdout:string}
     */
    private function runCli(string $workingDirectory, array $env): array
    {
        $command = array('php', __DIR__ . '/../../scripts/prepare-release.php');
        $descriptors = array(
            0 => array('pipe', 'r'),
            1 => array('pipe', 'w'),
            2 => array('pipe', 'w'),
        );

        $process = proc_open($command, $descriptors, $pipes, $workingDirectory, array_merge($_ENV, $env));
        self::assertIsResource($process);

        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);

        return array(
            'exitCode' => $exitCode,
            'stdout' => is_string($stdout) ? $stdout : '',
            'stderr' => is_string($stderr) ? $stderr : '',
        );
    }
}
