<?php

declare(strict_types=1);

use PdfGate\Internal\Docs\SiteBuilder;

$root = projectRoot();
$autoloadPath = $root . '/vendor/autoload.php';

if (!is_file($autoloadPath)) {
    fwrite(STDERR, "Missing Composer autoloader. Run composer install before building docs.\n");
    exit(1);
}

require $autoloadPath;

$docsDirectory = environmentPath('DOCS_SOURCE_DIR', $root . '/docs');
$outputDirectory = environmentPath('DOCS_SITE_OUTPUT_DIR', $root . '/build/docs/site');

try {
    $builder = new SiteBuilder();
    $builder->build($docsDirectory, $outputDirectory);
} catch (Throwable $exception) {
    fwrite(STDERR, $exception->getMessage() . "\n");
    exit(1);
}

fwrite(STDOUT, "Curated docs generated in build/docs/site\n");

function projectRoot(): string
{
    $configuredRoot = getenv('PDFGATE_PROJECT_ROOT');
    if (is_string($configuredRoot) && trim($configuredRoot) !== '') {
        return trim($configuredRoot);
    }

    return dirname(__DIR__);
}

function environmentPath(string $name, string $default): string
{
    $value = getenv($name);
    if (!is_string($value) || trim($value) === '') {
        return $default;
    }

    return trim($value);
}
