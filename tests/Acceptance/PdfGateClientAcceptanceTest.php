<?php

declare(strict_types=1);

namespace PdfGate\Tests\Acceptance;

use PdfGate\Exception\ApiException;
use PdfGate\PdfGateClient;
use PHPUnit\Framework\TestCase;

final class PdfGateClientAcceptanceTest extends TestCase
{
    private const ENVELOPE_SOURCE_HTML = <<<'HTML'
<html>
<body style="font-family: Arial, sans-serif; padding: 40px;">
  <h2>Agreement</h2>
  <p>Please review and complete the required fields below.</p>
  <div style="margin-top: 30px;">
    <label>Full Name</label><br />
    <input type="text" name="recipient-name" style="width: 300px; height: 30px;" />
  </div>
  <div style="margin-top: 30px;">
    <label>Signature</label><br />
    <pdfgate-signature-field name="signature" style="width: 200px; height: 200px;"></pdfgate-signature-field>
  </div>
  <div style="margin-top: 30px;">
    <label>Date</label><br />
    <input type="datetime-local" name="signature-date" pdfgate-auto-fill="true" style="width: 200px; height: 30px;" />
  </div>
</body>
</html>
HTML;

    /** @var PdfGateClient */
    private static $client;

    /** @var string */
    private static $documentId;

    /** @var string */
    private static $envelopeSourceDocumentId;

    public static function setUpBeforeClass(): void
    {
        $apiKey = getenv('PDFGATE_API_KEY');

        if (!$apiKey) {
            self::markTestSkipped('Set PDFGATE_API_KEY to run acceptance tests.');
        }

        self::$client = new PdfGateClient($apiKey);
        $shared = self::$client->generatePdf(array(
            'html' => '<html><body><p>Shared source doc.</p><input name="field1" value="x" /></body></html>',
            'enableFormFields' => true,
            'metadata' => array('suite' => 'acceptance-shared-source'),
        ));
        self::$documentId = $shared->getId();

        $envelopeSource = self::$client->generatePdf(array(
            'html' => self::ENVELOPE_SOURCE_HTML,
            'enableFormFields' => true,
            'metadata' => array('suite' => 'acceptance-envelope-source'),
        ));
        self::$envelopeSourceDocumentId = $envelopeSource->getId();
    }

    public function testGeneratePdfReturnsDocument(): void
    {
        $response = self::$client->generatePdf(array(
            'html' => '<html><body><div id="cookieDialog"><button class="popupClose">Close</button></div><p>Generate endpoint argument coverage.</p><input name="fieldA" value="value-a" /></body></html>',
            'pageSizeType' => 'a4',
            'margin' => array(
                'top' => '10px',
                'bottom' => '12px',
                'left' => '8px',
                'right' => '8px',
            ),
            'clickSelectorChainSetup' => array(
                'ignoreFailingChains' => true,
                'chains' => array(
                    array('selectors' => array('#cookieDialog')),
                    array('selectors' => array('.popupClose')),
                ),
            ),
            'printBackground' => true,
            'enableFormFields' => true,
            'metadata' => array(
                'suite' => 'acceptance-generate-args',
                'case' => 'full-argument-payload',
            ),
        ));

        self::assertNotSame('', $response->getId());
        self::assertSame('completed', $response->getStatus());
        self::assertSame('from_html', $response->getType());
    }

    public function testUploadFileReturnsDocumentMetadata(): void
    {
        $uploadFilePath = __DIR__ . '/fixtures/upload-source.pdf';
        if (!file_exists($uploadFilePath)) {
            self::markTestSkipped('Missing acceptance fixture: tests/Acceptance/fixtures/upload-source.pdf');
        }

        $uploaded = self::$client->uploadFile(array(
            'file' => new \CURLFile($uploadFilePath, 'application/pdf', 'upload-source.pdf'),
            'metadata' => array('suite' => 'acceptance-upload'),
        ));

        self::assertNotSame('', $uploaded->getId());
        self::assertSame('completed', $uploaded->getStatus());
        self::assertSame('uploaded', $uploaded->getType());
        self::assertGreaterThan(0, $uploaded->getSize());
    }

    public function testFlattenPdfReturnsDocumentMetadata(): void
    {
        $flattened = self::$client->flattenPdf(array(
            'documentId' => self::$documentId,
            'metadata' => array('suite' => 'acceptance-flatten'),
        ));

        self::assertNotSame('', $flattened->getId());
        self::assertSame('completed', $flattened->getStatus());
        self::assertSame('flattened', $flattened->getType());
        self::assertSame(self::$documentId, $flattened->getDerivedFrom());
    }

    public function testCompressPdfReturnsDocumentMetadata(): void
    {
        $compressed = self::$client->compressPdf(array(
            'documentId' => self::$documentId,
            'linearize' => true,
            'metadata' => array('suite' => 'acceptance-compress'),
        ));

        self::assertNotSame('', $compressed->getId());
        self::assertSame('completed', $compressed->getStatus());
        self::assertSame('compressed', $compressed->getType());
        self::assertSame(self::$documentId, $compressed->getDerivedFrom());
    }

    public function testProtectPdfReturnsDocumentMetadata(): void
    {
        $protected = self::$client->protectPdf(array(
            'documentId' => self::$documentId,
            'algorithm' => 'AES256',
            'ownerPassword' => 'owner-secret',
            'userPassword' => 'user-secret',
            'disablePrint' => true,
            'disableCopy' => true,
            'disableEditing' => true,
            'encryptMetadata' => true,
            'metadata' => array('suite' => 'acceptance-protect'),
        ));

        self::assertNotSame('', $protected->getId());
        self::assertSame('completed', $protected->getStatus());
        self::assertSame('encrypted', $protected->getType());
        self::assertSame(self::$documentId, $protected->getDerivedFrom());
    }

    public function testWatermarkPdfTextReturnsDocumentMetadata(): void
    {
        $watermarked = self::$client->watermarkPdf(array(
            'documentId' => self::$documentId,
            'type' => 'text',
            'text' => 'ACCEPTANCE WATERMARK',
            'fontColor' => '#AA33CC',
            'rotate' => 30,
            'opacity' => 0.2,
            'metadata' => array('suite' => 'acceptance-watermark-text'),
        ));

        self::assertNotSame('', $watermarked->getId());
        self::assertSame('completed', $watermarked->getStatus());
        self::assertSame('watermarked', $watermarked->getType());
        self::assertSame(self::$documentId, $watermarked->getDerivedFrom());
    }

    public function testWatermarkPdfImageReturnsDocumentMetadata(): void
    {
        $watermarkFilePath = __DIR__ . '/fixtures/watermark.png';
        if (!file_exists($watermarkFilePath)) {
            self::markTestSkipped('Missing acceptance fixture: tests/Acceptance/fixtures/watermark.png');
        }

        $watermarked = self::$client->watermarkPdf(array(
            'documentId' => self::$documentId,
            'type' => 'image',
            'watermark' => new \CURLFile($watermarkFilePath, 'image/png', 'watermark.png'),
            'imageWidth' => 96,
            'imageHeight' => 96,
            'opacity' => 0.25,
            'metadata' => array('suite' => 'acceptance-watermark-image'),
        ));

        self::assertNotSame('', $watermarked->getId());
        self::assertSame('completed', $watermarked->getStatus());
        self::assertSame('watermarked', $watermarked->getType());
        self::assertSame(self::$documentId, $watermarked->getDerivedFrom());
    }

    public function testExtractPdfFormDataReturnsArrayData(): void
    {
        $extracted = self::$client->extractPdfFormData(array(
            'documentId' => self::$documentId,
        ));

        self::assertIsArray($extracted);
    }

    public function testGetDocumentReturnsDocumentMetadata(): void
    {
        $document = self::$client->getDocument(
            self::$documentId,
            array('preSignedUrlExpiresIn' => 1200)
        );

        self::assertSame(self::$documentId, $document->getId());
        self::assertSame('completed', $document->getStatus());
        self::assertSame('from_html', $document->getType());
    }

    public function testCreateEnvelopeReturnsEnvelopeMetadata(): void
    {
        $envelope = self::$client->createEnvelope(array(
            'requesterName' => 'Acceptance Suite',
            'documents' => array(
                array(
                    'sourceDocumentId' => self::$envelopeSourceDocumentId,
                    'name' => 'Agreement',
                    'recipients' => array(
                        array(
                            'email' => 'anna@example.com',
                            'name' => 'Anna Smith',
                        ),
                    ),
                ),
            ),
            'metadata' => array(
                'suite' => 'acceptance-envelope',
                'customerId' => 'cus_123',
            ),
        ));

        self::assertNotSame('', $envelope->getId());
        self::assertSame('created', $envelope->getStatus());
        self::assertNotNull($envelope->getCreatedAt());
        self::assertNotEmpty($envelope->getDocuments());
        self::assertSame(
            array(
                'suite' => 'acceptance-envelope',
                'customerId' => 'cus_123',
            ),
            $envelope->getMetadata()
        );
    }

    public function testSendEnvelopeReturnsInProgressEnvelope(): void
    {
        $envelope = self::$client->createEnvelope(array(
            'requesterName' => 'Acceptance Suite',
            'documents' => array(
                array(
                    'sourceDocumentId' => self::$envelopeSourceDocumentId,
                    'name' => 'Agreement',
                    'recipients' => array(
                        array(
                            'email' => 'anna@example.com',
                            'name' => 'Anna Smith',
                        ),
                    ),
                ),
            ),
            'metadata' => array(
                'suite' => 'acceptance-envelope-send',
                'customerId' => 'cus_123',
            ),
        ));

        $sentEnvelope = self::$client->sendEnvelope($envelope->getId());

        self::assertSame($envelope->getId(), $sentEnvelope->getId());
        self::assertSame('in_progress', $sentEnvelope->getStatus());
        self::assertNotNull($sentEnvelope->getCreatedAt());
        self::assertNotEmpty($sentEnvelope->getDocuments());
        self::assertSame(
            array(
                'suite' => 'acceptance-envelope-send',
                'customerId' => 'cus_123',
            ),
            $sentEnvelope->getMetadata()
        );
    }

    public function testGetFileReturnsPdfStream(): void
    {
        $stream = self::$client->getFile(self::$documentId);

        self::assertIsResource($stream);
        $binary = stream_get_contents($stream);
        fclose($stream);

        self::assertIsString($binary);
        self::assertNotSame('', $binary);
        self::assertStringStartsWith('%PDF-', $binary);
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
