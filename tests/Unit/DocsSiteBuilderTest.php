<?php

declare(strict_types=1);

namespace PdfGate\Tests\Unit;

use PdfGate\Internal\Docs\SiteBuilder;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class DocsSiteBuilderTest extends TestCase
{
    public function testSiteBuilderRendersCuratedDocsIntoCombinedArtifact(): void
    {
        $workspace = $this->createWorkspace();

        $this->writeFile(
            $workspace . '/docs/index.md',
            <<<MD
            # SDK Docs

            - [Quickstart](guides/quickstart.md)
            - [API Guide](guides/api.md)
            MD
        );
        $this->writeFile(
            $workspace . '/docs/guides/quickstart.md',
            <<<MD
            # Quickstart

            See the [API Guide](api.md).
            MD
        );
        $this->writeFile(
            $workspace . '/docs/guides/api.md',
            <<<MD
            # API Guide

            Return to the [landing page](../index.md).
            MD
        );
        $this->writeFile($workspace . '/build/docs/site/api/index.html', '<html>api</html>');
        $this->writeFile($workspace . '/build/docs/site/stale.html', '<html>stale</html>');

        $builder = new SiteBuilder();
        $builder->build($workspace . '/docs', $workspace . '/build/docs/site');

        self::assertFileExists($workspace . '/build/docs/site/index.html');
        self::assertFileExists($workspace . '/build/docs/site/guides/quickstart/index.html');
        self::assertFileExists($workspace . '/build/docs/site/guides/api/index.html');
        self::assertFileExists($workspace . '/build/docs/site/api/index.html');
        self::assertFileDoesNotExist($workspace . '/build/docs/site/stale.html');

        $indexHtml = (string) file_get_contents($workspace . '/build/docs/site/index.html');
        $quickstartHtml = (string) file_get_contents($workspace . '/build/docs/site/guides/quickstart/index.html');
        $apiGuideHtml = (string) file_get_contents($workspace . '/build/docs/site/guides/api/index.html');

        self::assertStringContainsString('href="guides/quickstart/"', $indexHtml);
        self::assertStringContainsString('href="guides/api/"', $indexHtml);
        self::assertStringContainsString('href="api/"', $indexHtml);
        self::assertStringContainsString('href="../api/"', $quickstartHtml);
        self::assertStringContainsString('href="../../api/"', $quickstartHtml);
        self::assertStringContainsString('href="../../"', $apiGuideHtml);
    }

    public function testSiteBuilderFailsWhenApiReferenceIsMissing(): void
    {
        $workspace = $this->createWorkspace();
        $this->writeFile($workspace . '/docs/index.md', "# SDK Docs\n");
        $this->writeFile($workspace . '/docs/guides/quickstart.md', "# Quickstart\n");

        $builder = new SiteBuilder();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('API reference is missing from the combined site build');

        $builder->build($workspace . '/docs', $workspace . '/build/docs/site');
    }

    public function testGenerateApiDocsScriptWritesToCombinedApiDirectory(): void
    {
        $workspace = $this->createWorkspace();
        $this->writeFile($workspace . '/phpdoc.xml', "<phpdocumentor />\n");

        $fakePhpDoc = $workspace . '/fake-phpdoc.sh';
        $this->writeFile(
            $fakePhpDoc,
            <<<'SH'
#!/usr/bin/env bash
set -euo pipefail
mkdir -p build/docs/site/api
printf '<html>api</html>' > build/docs/site/api/index.html
SH
        );
        chmod($fakePhpDoc, 0755);

        $result = $this->runPhpScript(__DIR__ . '/../../scripts/generate-api-docs.php', array(
            'PDFGATE_PROJECT_ROOT' => $workspace,
            'PHPDOC_BIN' => $fakePhpDoc,
        ));

        self::assertSame(0, $result['exitCode'], $result['stderr']);
        self::assertStringContainsString('API docs generated in build/docs/site/api', $result['stdout']);
        self::assertFileExists($workspace . '/build/docs/site/api/index.html');
    }

    public function testBuildDocsSiteScriptRendersCombinedSiteArtifact(): void
    {
        $workspace = $this->createWorkspace();

        $this->writeFile(
            $workspace . '/vendor/autoload.php',
            "<?php\nrequire " . var_export(dirname(__DIR__, 2) . '/vendor/autoload.php', true) . ";\n"
        );
        $this->writeFile(
            $workspace . '/docs/index.md',
            <<<MD
            # SDK Docs

            - [Quickstart](guides/quickstart.md)
            MD
        );
        $this->writeFile(
            $workspace . '/docs/guides/quickstart.md',
            <<<MD
            # Quickstart

            Back to the [landing page](../index.md).
            MD
        );
        $this->writeFile($workspace . '/build/docs/site/api/index.html', '<html>api</html>');

        $result = $this->runPhpScript(__DIR__ . '/../../scripts/build-docs-site.php', array(
            'PDFGATE_PROJECT_ROOT' => $workspace,
        ));

        self::assertSame(0, $result['exitCode'], $result['stderr']);
        self::assertStringContainsString('Curated docs generated in build/docs/site', $result['stdout']);
        self::assertFileExists($workspace . '/build/docs/site/index.html');
        self::assertFileExists($workspace . '/build/docs/site/guides/quickstart/index.html');
        self::assertFileExists($workspace . '/build/docs/site/api/index.html');
    }

    private function createWorkspace(): string
    {
        $workspace = sys_get_temp_dir() . '/pdfgate-docs-' . bin2hex(random_bytes(8));
        mkdir($workspace, 0777, true);

        return $workspace;
    }

    private function writeFile(string $path, string $contents): void
    {
        $directory = dirname($path);
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        file_put_contents($path, $contents);
    }

    /**
     * @param array<string, string> $env
     * @return array{exitCode:int,stderr:string,stdout:string}
     */
    private function runPhpScript(string $scriptPath, array $env): array
    {
        $command = array('php', $scriptPath);
        $descriptors = array(
            0 => array('pipe', 'r'),
            1 => array('pipe', 'w'),
            2 => array('pipe', 'w'),
        );

        $process = proc_open($command, $descriptors, $pipes, dirname(__DIR__, 2), array_merge($_ENV, $env));
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
