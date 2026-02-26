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
        self::assertNotSame('', $response->getFileUrl());
        self::assertSame('completed', $response->getStatus());
        self::assertSame('from_html', $response->getType());
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
