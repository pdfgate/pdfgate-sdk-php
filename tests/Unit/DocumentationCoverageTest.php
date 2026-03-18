<?php

declare(strict_types=1);

namespace PdfGate\Tests\Unit;

use PdfGate\PdfGateClient;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class DocumentationCoverageTest extends TestCase
{
    public function testDocumentationIndexLinksToAllGuideAndReferencePages(): void
    {
        $indexPath = __DIR__ . '/../../docs/index.md';
        $index = file_get_contents($indexPath);
        self::assertNotFalse($index, 'Unable to read docs/index.md');

        $expectedLinks = array(
            'guides/quickstart.md',
            'guides/api.md',
            'guides/error-handling.md',
            'guides/testing.md',
        );

        foreach ($expectedLinks as $link) {
            self::assertStringContainsString(
                "({$link})",
                $index,
                sprintf('Missing docs index link for %s.', $link)
            );
        }

        self::assertStringContainsString(
            '(/api/)',
            $index,
            'Docs index should point to the published API reference path.'
        );
    }
}
