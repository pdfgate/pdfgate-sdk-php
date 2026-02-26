<?php

declare(strict_types=1);

namespace PdfGate\Tests\Acceptance;

use PdfGate\Exception\ApiException;
use PdfGate\PdfGateClient;
use PHPUnit\Framework\TestCase;

final class PdfGateClientAcceptanceTest extends TestCase
{
    /** @var PdfGateClient */
    private static $client;

    public static function setUpBeforeClass(): void
    {
        $apiKey = getenv('PDFGATE_API_KEY');

        if (!$apiKey) {
            self::markTestSkipped('Set PDFGATE_API_KEY to run acceptance tests.');
        }

        self::$client = new PdfGateClient($apiKey);
    }

    public function testGeneratePdfReturnsDocument(): void
    {
        $response = self::$client->generatePdf(array(
            'html' => '<html><body><p>Generate endpoint check.</p></body></html>',
            'metadata' => array('suite' => 'acceptance'),
        ));

        self::assertNotSame('', $response->getId());
        self::assertSame('completed', $response->getStatus());
        self::assertSame('from_html', $response->getType());
    }

    public function testFlattenPdfReturnsDocumentMetadata(): void
    {
        $generated = self::$client->generatePdf(array(
            'html' => '<html><body><p>Flatten endpoint check.</p><input name="field1" value="x" /></body></html>',
            'enableFormFields' => true,
            'metadata' => array('suite' => 'acceptance-flatten-source'),
        ));

        $flattened = self::$client->flattenPdf(array(
            'documentId' => $generated->getId(),
            'metadata' => array('suite' => 'acceptance-flatten'),
        ));

        self::assertNotSame('', $flattened->getId());
        self::assertSame('completed', $flattened->getStatus());
        self::assertSame('flattened', $flattened->getType());
        self::assertSame($generated->getId(), $flattened->getDerivedFrom());
    }

    public function testExtractPdfFormDataReturnsArrayData(): void
    {
        $generated = self::$client->generatePdf(array(
            'html' => '<html><body><p>Extract endpoint check.</p><input name="field1" value="x" /></body></html>',
            'enableFormFields' => true,
            'metadata' => array('suite' => 'acceptance-extract-source'),
        ));

        $extracted = self::$client->extractPdfFormData(array(
            'documentId' => $generated->getId(),
        ));

        self::assertIsArray($extracted);
    }

    public function testAuthFailureBehaviorReturnsApiException(): void
    {
        $client = new PdfGateClient('test_invalid_key');

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('status 401');

        $client->generatePdf(array('html' => '<html><body><p>Auth failure check.</p></body></html>'));
    }

    public function testInvalidRequestReturnsBadRequestApiException(): void
    {
        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('status 400');

        // Invalid request: API requires either "html" or "url".
        self::$client->generatePdf(array());
    }
}
