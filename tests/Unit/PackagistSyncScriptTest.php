<?php

declare(strict_types=1);

namespace PdfGate\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class PackagistSyncScriptTest extends TestCase
{
    public function testDryRunDoesNotRequireSecretsAndPrintsRequestDetails(): void
    {
        $result = $this->runScript(array(
            'EVENT_NAME' => 'workflow_dispatch',
            'SYNC_MODE' => 'dry-run',
        ));

        self::assertSame(0, $result['exitCode'], $result['stderr']);
        self::assertStringContainsString('Packagist sync mode: dry-run', $result['stdout']);
        self::assertStringContainsString('Target URL: https://packagist.org/api/update-package', $result['stdout']);
        self::assertStringContainsString('Request payload: {"repository":"https://github.com/pdfgate/pdfgate-sdk-php"}', $result['stdout']);
        self::assertStringContainsString('no network request sent', $result['stdout']);
    }

    public function testLiveSyncRequiresSecrets(): void
    {
        $result = $this->runScript(array(
            'EVENT_NAME' => 'release',
            'SYNC_MODE' => 'live',
        ));

        self::assertNotSame(0, $result['exitCode']);
        self::assertStringContainsString(
            'PACKAGIST_USERNAME and PACKAGIST_TOKEN must be configured for live Packagist sync.',
            $result['stderr']
        );
    }

    public function testLiveSyncTargetsPackagistEndpoint(): void
    {
        $curlLogPath = tempnam(sys_get_temp_dir(), 'curl-log-');
        $curlBinPath = $this->createFakeCurlBinary();

        $result = $this->runScript(array(
            'EVENT_NAME' => 'release',
            'SYNC_MODE' => 'live',
            'PACKAGIST_USERNAME' => 'user',
            'PACKAGIST_TOKEN' => 'token',
            'CURL_BIN' => $curlBinPath,
            'CURL_LOG_FILE' => $curlLogPath,
        ));

        $curlLog = (string) file_get_contents($curlLogPath);

        self::assertSame(0, $result['exitCode'], $result['stderr']);
        self::assertStringContainsString('Packagist sync successful', $result['stdout']);
        self::assertStringContainsString('https://packagist.org/api/update-package', $curlLog);
        self::assertStringContainsString('{"repository":"https://github.com/pdfgate/pdfgate-sdk-php"}', $curlLog);
    }

    /**
     * @param array<string, string> $env
     * @return array{exitCode:int,stdout:string,stderr:string}
     */
    private function runScript(array $env): array
    {
        $command = array('bash', __DIR__ . '/../../scripts/sync-packagist.sh');
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

    private function createFakeCurlBinary(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'fake-curl-');
        self::assertNotFalse($path);

        file_put_contents(
            $path,
            <<<'BASH'
#!/usr/bin/env bash
set -euo pipefail

output_file=""

for ((i=1; i<=$#; i++)); do
  arg="${!i}"
  if [ "$arg" = "--output" ]; then
    next=$((i + 1))
    output_file="${!next}"
  fi
done

printf '%s\n' "$*" >> "${CURL_LOG_FILE}"
printf '{"status":"ok"}' > "$output_file"
printf '200'
BASH
        );
        chmod($path, 0755);

        return $path;
    }
}
