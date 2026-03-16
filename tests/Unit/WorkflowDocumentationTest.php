<?php

declare(strict_types=1);

namespace PdfGate\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class WorkflowDocumentationTest extends TestCase
{
    public function testReleaseWorkflowExposesManualTestInputsAndUsesScriptedPreparation(): void
    {
        $workflow = (string) file_get_contents(__DIR__ . '/../../.github/workflows/release.yml');

        self::assertStringContainsString('workflow_dispatch:', $workflow);
        self::assertStringContainsString('test_tag:', $workflow);
        self::assertStringContainsString('release_mode:', $workflow);
        self::assertStringContainsString('./scripts/prepare-release.sh', $workflow);
        self::assertStringContainsString('Create GitHub prerelease (manual test)', $workflow);
    }

    public function testPackagistWorkflowDefaultsManualRunsToDryRunAndUsesScriptedSync(): void
    {
        $workflow = (string) file_get_contents(__DIR__ . '/../../.github/workflows/packagist-sync.yml');

        self::assertStringContainsString('workflow_dispatch:', $workflow);
        self::assertStringContainsString('sync_mode:', $workflow);
        self::assertStringContainsString("default: 'dry-run'", $workflow);
        self::assertStringContainsString('./scripts/sync-packagist.sh', $workflow);
        self::assertStringContainsString('Simulate Packagist sync (dry-run)', $workflow);
    }

    public function testReadmeDocumentsSafeWorkflowTesting(): void
    {
        $readme = (string) file_get_contents(__DIR__ . '/../../README.md');

        self::assertStringContainsString('test-2026-03-16-1', $readme);
        self::assertStringContainsString('## [Unreleased]', $readme);
        self::assertStringContainsString('sync_mode=dry-run', $readme);
        self::assertStringContainsString('does not send a network request to Packagist', $readme);
    }
}
