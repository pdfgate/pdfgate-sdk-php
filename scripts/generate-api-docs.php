<?php

declare(strict_types=1);

$root = projectRoot();

chdir($root);

$phpDocBin = detectPhpDocBinary($root);
if ($phpDocBin === null) {
    fwrite(STDERR, "Unable to find phpDocumentor binary.\n");
    fwrite(STDERR, "Set PHPDOC_BIN, install phpDocumentor globally, or place a PHAR at build/tools/phpDocumentor.phar.\n");
    exit(1);
}

ensureDirectory($root . '/build/docs');
ensureDirectory($root . '/build/docs/site');
ensureDirectory($root . '/build/docs/site/api');
ensureDirectory($root . '/build/docs/.phpdoc/cache');

$configPath = $root . '/phpdoc.xml';
if (!is_file($configPath)) {
    fwrite(STDERR, "Missing phpDocumentor config file at phpdoc.xml.\n");
    exit(1);
}

$command = $phpDocBin . ' -c ' . escapeshellarg($configPath);
passthru($command, $exitCode);

if ($exitCode !== 0) {
    fwrite(STDERR, "phpDocumentor failed with exit code {$exitCode}.\n");
    exit($exitCode);
}

fwrite(STDOUT, "API docs generated in build/docs/site/api\n");

function projectRoot(): string
{
    $configuredRoot = getenv('PDFGATE_PROJECT_ROOT');
    if (is_string($configuredRoot) && trim($configuredRoot) !== '') {
        return trim($configuredRoot);
    }

    return dirname(__DIR__);
}

function detectPhpDocBinary(string $root): ?string
{
    $envBin = getenv('PHPDOC_BIN');
    if (is_string($envBin) && trim($envBin) !== '') {
        return trim($envBin);
    }

    $projectBinary = $root . '/vendor/bin/phpdoc';
    if (is_file($projectBinary) && is_executable($projectBinary)) {
        return escapeshellarg($projectBinary);
    }

    $pharPath = $root . '/build/tools/phpDocumentor.phar';
    if (is_file($pharPath)) {
        return 'php ' . escapeshellarg($pharPath);
    }

    $globalBinary = trim((string) shell_exec('command -v phpdoc 2>/dev/null'));
    if ($globalBinary !== '') {
        return escapeshellarg($globalBinary);
    }

    return null;
}

function ensureDirectory(string $path): void
{
    if (is_dir($path)) {
        return;
    }

    if (!mkdir($path, 0777, true) && !is_dir($path)) {
        fwrite(STDERR, "Failed to create directory: {$path}\n");
        exit(1);
    }
}
