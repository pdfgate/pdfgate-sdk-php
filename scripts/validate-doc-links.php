<?php

declare(strict_types=1);

$root = projectRoot();
chdir($root);

$files = array();
$entryFiles = array(
    $root . '/README.md',
    $root . '/docs/index.md',
);

foreach ($entryFiles as $file) {
    if (is_file($file)) {
        $files[] = $file;
    }
}

$docsIterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root . '/docs', FilesystemIterator::SKIP_DOTS)
);

foreach ($docsIterator as $item) {
    if ($item instanceof SplFileInfo && $item->isFile() && strtolower($item->getExtension()) === 'md') {
        $files[] = $item->getPathname();
    }
}

$files = array_values(array_unique($files));
$errors = array();

foreach ($files as $filePath) {
    $contents = file_get_contents($filePath);
    if ($contents === false) {
        $errors[] = relativePath($root, $filePath) . ': unable to read file';
        continue;
    }

    if (!preg_match_all('/\[[^\]]+\]\(([^)]+)\)/', $contents, $matches)) {
        continue;
    }

    foreach ($matches[1] as $destination) {
        $destination = trim($destination);
        if ($destination === '' || isExternalLink($destination) || strpos($destination, '#') === 0) {
            continue;
        }

        $destinationPath = preg_replace('/#.*/', '', $destination);
        if ($destinationPath === null || $destinationPath === '') {
            continue;
        }

        if (!isValidLinkDestination($root, $filePath, $destinationPath)) {
            $errors[] = relativePath($root, $filePath) . ': broken link -> ' . $destination;
        }
    }
}

if ($errors !== array()) {
    fwrite(STDERR, "Broken markdown links found:\n");
    foreach ($errors as $error) {
        fwrite(STDERR, ' - ' . $error . "\n");
    }

    exit(1);
}

fwrite(STDOUT, 'Markdown links validated for README.md and docs/.'."\n");

function projectRoot(): string
{
    $configuredRoot = getenv('PDFGATE_PROJECT_ROOT');
    if (is_string($configuredRoot) && trim($configuredRoot) !== '') {
        return trim($configuredRoot);
    }

    return dirname(__DIR__);
}

function isExternalLink(string $link): bool
{
    return preg_match('/^(https?:|mailto:|tel:)/i', $link) === 1;
}

function isValidLinkDestination(string $root, string $filePath, string $destinationPath): bool
{
    $resolvedPath = resolvePath(dirname($filePath), $destinationPath);
    if ($resolvedPath !== null && is_file($resolvedPath)) {
        return true;
    }

    foreach (publishedCandidates($root, $destinationPath) as $candidate) {
        if (is_file($candidate)) {
            return true;
        }
    }

    return false;
}

/**
 * @return list<string>
 */
function publishedCandidates(string $root, string $destinationPath): array
{
    if ($destinationPath === '' || strpos($destinationPath, DIRECTORY_SEPARATOR) !== 0) {
        return array();
    }

    $siteRoot = $root . '/build/docs/site';
    $trimmedPath = trim($destinationPath, DIRECTORY_SEPARATOR);

    if ($trimmedPath === '') {
        return array($siteRoot . '/index.html');
    }

    return array(
        $siteRoot . '/' . $trimmedPath,
        $siteRoot . '/' . $trimmedPath . '/index.html',
        $siteRoot . '/' . $trimmedPath . '.html',
    );
}

function resolvePath(string $baseDir, string $path): ?string
{
    if ($path === '') {
        return null;
    }

    $candidate = strpos($path, DIRECTORY_SEPARATOR) === 0
        ? $path
        : $baseDir . DIRECTORY_SEPARATOR . $path;
    $isAbsolute = strpos($candidate, DIRECTORY_SEPARATOR) === 0;

    $parts = preg_split('~[\\/]+~', $candidate);
    if ($parts === false) {
        return null;
    }

    $normalized = array();
    foreach ($parts as $part) {
        if ($part === '' || $part === '.') {
            continue;
        }

        if ($part === '..') {
            if ($normalized !== array()) {
                array_pop($normalized);
            }
            continue;
        }

        $normalized[] = $part;
    }

    return ($isAbsolute ? DIRECTORY_SEPARATOR : '') . implode(DIRECTORY_SEPARATOR, $normalized);
}

function relativePath(string $root, string $path): string
{
    if (strpos($path, $root . DIRECTORY_SEPARATOR) === 0) {
        return substr($path, strlen($root . DIRECTORY_SEPARATOR));
    }

    return $path;
}
