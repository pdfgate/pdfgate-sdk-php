<?php

declare(strict_types=1);

namespace PdfGate\Internal\Docs;

use Parsedown;
use RuntimeException;

final class SiteBuilder
{
    private Parsedown $markdown;

    public function __construct(?Parsedown $markdown = null)
    {
        $this->markdown = $markdown ?? new Parsedown();
        $this->markdown->setMarkupEscaped(true);
        $this->markdown->setSafeMode(true);
    }

    public function build(string $docsDirectory, string $outputDirectory): void
    {
        $docsDirectory = $this->normalizePath($docsDirectory);
        $outputDirectory = $this->normalizePath($outputDirectory);
        $indexPath = $docsDirectory . '/index.md';
        $guidesDirectory = $docsDirectory . '/guides';
        $apiIndexPath = $outputDirectory . '/api/index.html';

        if (!is_dir($docsDirectory)) {
            throw new RuntimeException(sprintf('Docs source directory does not exist: %s', $docsDirectory));
        }

        if (!is_file($indexPath)) {
            throw new RuntimeException(sprintf('Docs source file is missing: %s', $indexPath));
        }

        if (!is_dir($guidesDirectory)) {
            throw new RuntimeException(sprintf('Guides source directory does not exist: %s', $guidesDirectory));
        }

        if (!is_file($apiIndexPath)) {
            throw new RuntimeException(sprintf('API reference is missing from the combined site build: %s', $apiIndexPath));
        }

        $this->ensureDirectory($outputDirectory);
        $this->clearOutputDirectory($outputDirectory, array('api'));

        $documents = $this->discoverDocuments($docsDirectory);
        $indexContents = file_get_contents($indexPath);
        if ($indexContents === false) {
            throw new RuntimeException(sprintf('Failed to read docs source file: %s', $indexPath));
        }

        $navigation = $this->buildNavigation($indexContents, $indexPath, $docsDirectory, $outputDirectory);

        foreach ($documents as $sourcePath) {
            $outputPath = $this->outputPathForDocument($sourcePath, $docsDirectory, $outputDirectory);
            $outputDirectoryPath = dirname($outputPath);

            $this->ensureDirectory($outputDirectoryPath);

            $contents = $sourcePath === $indexPath ? $indexContents : file_get_contents($sourcePath);
            if (!is_string($contents)) {
                throw new RuntimeException(sprintf('Failed to read docs source file: %s', $sourcePath));
            }

            $rewrittenMarkdown = $this->rewriteMarkdownLinks($contents, $sourcePath, $docsDirectory, $outputDirectory);
            $documentTitle = $this->extractTitle($contents, $sourcePath);
            $html = $this->renderDocument($documentTitle, $rewrittenMarkdown, $outputPath, $navigation, $outputDirectory);

            if (file_put_contents($outputPath, $html) === false) {
                throw new RuntimeException(sprintf('Failed to write rendered documentation page: %s', $outputPath));
            }
        }
    }

    /**
     * @return list<string>
     */
    private function discoverDocuments(string $docsDirectory): array
    {
        $documents = array($docsDirectory . '/index.md');
        $guidePaths = glob($docsDirectory . '/guides/*.md');

        if ($guidePaths === false) {
            throw new RuntimeException(sprintf('Failed to enumerate guide files in: %s', $docsDirectory . '/guides'));
        }

        sort($guidePaths);

        foreach ($guidePaths as $guidePath) {
            $documents[] = $this->normalizePath($guidePath);
        }

        return $documents;
    }

    /**
     * @return list<array{label:string,outputPath:string}>
     */
    private function buildNavigation(
        string $indexContents,
        string $indexPath,
        string $docsDirectory,
        string $outputDirectory
    ): array
    {
        $navigation = array();

        if (!preg_match_all('/\[(?<label>[^\]]+)\]\((?<destination>[^)]+)\)/', $indexContents, $matches, PREG_SET_ORDER)) {
            throw new RuntimeException(sprintf('Docs index must include at least one navigation link: %s', $indexPath));
        }

        foreach ($matches as $match) {
            $destination = trim($match['destination']);
            $navigation[] = array(
                'label' => trim($match['label']),
                'outputPath' => $this->outputPathForNavigationDestination(
                    $destination,
                    $indexPath,
                    $docsDirectory,
                    $outputDirectory
                ),
            );
        }

        return $navigation;
    }

    /**
     * @param list<array{label:string,outputPath:string}> $navigation
     */
    private function renderDocument(
        string $documentTitle,
        string $markdown,
        string $outputPath,
        array $navigation,
        string $outputDirectory
    ): string {
        $navigationMarkup = array();

        foreach ($navigation as $entry) {
            $href = $this->relativeHref(dirname($outputPath), $entry['outputPath']);
            $isCurrent = $entry['outputPath'] === $outputPath;
            $label = htmlspecialchars($entry['label'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $href = htmlspecialchars($href, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $className = $isCurrent ? ' class="current"' : '';
            $navigationMarkup[] = sprintf('<li%s><a href="%s">%s</a></li>', $className, $href, $label);
        }

        $pageTitle = htmlspecialchars(sprintf('%s | PDFGate SDK for PHP', $documentTitle), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $content = $this->markdown->text($markdown);

        $navigationItems = implode("\n          ", $navigationMarkup);
        $homeHref = htmlspecialchars($this->relativeHref(dirname($outputPath), $outputDirectory . '/index.html'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{$pageTitle}</title>
  <style>
    :root {
      color-scheme: light;
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
      line-height: 1.6;
    }

    body {
      margin: 0;
      background: #f5f7fb;
      color: #172033;
    }

    .layout {
      max-width: 1080px;
      margin: 0 auto;
      padding: 32px 20px 48px;
      display: grid;
      gap: 24px;
      grid-template-columns: minmax(220px, 260px) minmax(0, 1fr);
    }

    .sidebar,
    .content {
      background: #ffffff;
      border: 1px solid #d7dfeb;
      border-radius: 14px;
      box-shadow: 0 10px 30px rgba(23, 32, 51, 0.06);
    }

    .sidebar {
      padding: 20px;
      align-self: start;
      position: sticky;
      top: 20px;
    }

    .sidebar-title {
      margin: 0 0 16px;
      font-size: 1.25rem;
    }

    .content {
      padding: 32px;
      min-width: 0;
    }

    h1, h2, h3 {
      line-height: 1.25;
    }

    h1 {
      margin-top: 0;
      margin-bottom: 24px;
    }

    nav ul {
      list-style: none;
      margin: 0;
      padding: 0;
    }

    nav li + li {
      margin-top: 8px;
    }

    nav li.current a {
      font-weight: 700;
    }

    a {
      color: #0b5fff;
      text-decoration: none;
    }

    a:hover,
    a:focus {
      text-decoration: underline;
    }

    code,
    pre {
      font-family: "SFMono-Regular", Consolas, "Liberation Mono", monospace;
    }

    pre {
      overflow-x: auto;
      background: #0f1726;
      color: #eff5ff;
      padding: 16px;
      border-radius: 10px;
    }

    code {
      background: #eef3ff;
      padding: 0.1em 0.3em;
      border-radius: 4px;
    }

    pre code {
      background: transparent;
      padding: 0;
    }

    table {
      width: 100%;
      border-collapse: collapse;
    }

    th,
    td {
      border: 1px solid #d7dfeb;
      padding: 10px 12px;
      text-align: left;
    }

    blockquote {
      margin-left: 0;
      padding-left: 16px;
      border-left: 4px solid #d7dfeb;
      color: #3c4b67;
    }

    @media (max-width: 820px) {
      .layout {
        grid-template-columns: 1fr;
      }

      .sidebar {
        position: static;
      }

      .content {
        padding: 24px;
      }
    }
  </style>
</head>
<body>
  <div class="layout">
    <aside class="sidebar">
      <h2 class="sidebar-title"><a href="{$homeHref}">PDFGate SDK for PHP</a></h2>
      <nav aria-label="Documentation">
        <ul>
          {$navigationItems}
        </ul>
      </nav>
    </aside>
    <main class="content">
      {$content}
    </main>
  </div>
</body>
</html>
HTML;
    }

    private function rewriteMarkdownLinks(
        string $markdown,
        string $sourcePath,
        string $docsDirectory,
        string $outputDirectory
    ): string {
        $currentOutputPath = $this->outputPathForDocument($sourcePath, $docsDirectory, $outputDirectory);

        return (string) preg_replace_callback(
            '/\[(?<text>[^\]]+)\]\((?<destination>[^)]+)\)/',
            function (array $matches) use ($sourcePath, $docsDirectory, $currentOutputPath, $outputDirectory): string {
                $destination = trim($matches['destination']);

                if ($destination === '' || $this->isExternalLink($destination) || strpos($destination, '#') === 0) {
                    return $matches[0];
                }

                $fragment = '';
                $fragmentPosition = strpos($destination, '#');
                if ($fragmentPosition !== false) {
                    $fragment = substr($destination, $fragmentPosition);
                    $destination = substr($destination, 0, $fragmentPosition);
                }

                if ($destination === '' || substr($destination, -3) !== '.md') {
                    return $matches[0];
                }

                $resolvedSourcePath = $this->normalizePath(dirname($sourcePath) . '/' . $destination);
                if (strpos($resolvedSourcePath, $docsDirectory . '/') !== 0 && $resolvedSourcePath !== $docsDirectory . '/index.md') {
                    return $matches[0];
                }

                $targetOutputPath = $this->outputPathForDocument($resolvedSourcePath, $docsDirectory, $outputDirectory);
                $href = $this->relativeHref(dirname($currentOutputPath), $targetOutputPath) . $fragment;

                return sprintf('[%s](%s)', $matches['text'], $href);
            },
            $markdown
        ) ?? $markdown;
    }

    private function outputPathForDocument(string $sourcePath, string $docsDirectory, string $outputDirectory): string
    {
        $sourcePath = $this->normalizePath($sourcePath);
        $relativeSourcePath = ltrim(substr($sourcePath, strlen($docsDirectory)), '/');

        if ($relativeSourcePath === 'index.md') {
            return $outputDirectory . '/index.html';
        }

        if (preg_match('/^guides\/(.+)\.md$/', $relativeSourcePath, $matches) !== 1) {
            throw new RuntimeException(sprintf('Unsupported docs source path: %s', $sourcePath));
        }

        return sprintf('%s/guides/%s/index.html', $outputDirectory, $matches[1]);
    }

    private function outputPathForNavigationDestination(
        string $destination,
        string $indexPath,
        string $docsDirectory,
        string $outputDirectory
    ): string {
        if ($destination === '/api/') {
            return $outputDirectory . '/api/index.html';
        }

        if (substr($destination, -3) !== '.md') {
            throw new RuntimeException(sprintf('Unsupported navigation link in docs index: %s', $destination));
        }

        $resolvedSourcePath = $this->normalizePath(dirname($indexPath) . '/' . $destination);

        return $this->outputPathForDocument($resolvedSourcePath, $docsDirectory, $outputDirectory);
    }

    private function extractTitle(string $markdown, string $sourcePath): string
    {
        if (preg_match('/^#\s+(.+)$/m', $markdown, $matches) !== 1) {
            throw new RuntimeException(sprintf('Missing level-one heading in docs source file: %s', $sourcePath));
        }

        return trim($matches[1]);
    }

    private function ensureDirectory(string $path): void
    {
        if (is_dir($path)) {
            return;
        }

        if (!mkdir($path, 0777, true) && !is_dir($path)) {
            throw new RuntimeException(sprintf('Failed to create directory for generated docs: %s', $path));
        }
    }

    /**
     * @param list<string> $preservedEntries
     */
    private function clearOutputDirectory(string $outputDirectory, array $preservedEntries): void
    {
        $entries = scandir($outputDirectory);
        if ($entries === false) {
            throw new RuntimeException(sprintf('Failed to list combined docs output directory: %s', $outputDirectory));
        }

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..' || in_array($entry, $preservedEntries, true)) {
                continue;
            }

            $this->removePath($outputDirectory . '/' . $entry);
        }
    }

    private function removePath(string $path): void
    {
        if (is_link($path) || is_file($path)) {
            if (!unlink($path)) {
                throw new RuntimeException(sprintf('Failed to remove stale generated docs file: %s', $path));
            }

            return;
        }

        if (!is_dir($path)) {
            return;
        }

        $entries = scandir($path);
        if ($entries === false) {
            throw new RuntimeException(sprintf('Failed to inspect stale generated docs directory: %s', $path));
        }

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $this->removePath($path . '/' . $entry);
        }

        if (!rmdir($path)) {
            throw new RuntimeException(sprintf('Failed to remove stale generated docs directory: %s', $path));
        }
    }

    private function relativeHref(string $fromDirectory, string $toPath): string
    {
        $fromParts = explode('/', trim($this->normalizePath($fromDirectory), '/'));
        $toParts = explode('/', trim($this->normalizePath($toPath), '/'));

        while ($fromParts !== array() && $toParts !== array() && $fromParts[0] === $toParts[0]) {
            array_shift($fromParts);
            array_shift($toParts);
        }

        $relativeParts = array_merge(array_fill(0, count($fromParts), '..'), $toParts);
        $relativePath = implode('/', $relativeParts);

        if ($relativePath === 'index.html') {
            return './';
        }

        if (substr($relativePath, -11) === '/index.html') {
            return substr($relativePath, 0, -10);
        }

        return $relativePath;
    }

    private function isExternalLink(string $destination): bool
    {
        return preg_match('/^(https?:|mailto:|tel:)/i', $destination) === 1;
    }

    private function normalizePath(string $path): string
    {
        $isAbsolute = strpos($path, '/') === 0;
        $parts = preg_split('~[\\/]+~', $path);

        if ($parts === false) {
            throw new RuntimeException(sprintf('Unable to normalize path: %s', $path));
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

        return ($isAbsolute ? '/' : '') . implode('/', $normalized);
    }
}
