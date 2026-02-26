<?php

declare(strict_types=1);

namespace PdfGate\Tests\Unit;

use PdfGate\Dto\PdfGateDocumentMetadata;
use PdfGate\Exception\ApiException;
use PdfGate\Exception\InvalidConfigurationException;
use PdfGate\Exception\TransportException;
use PdfGate\Http\HttpRequest;
use PdfGate\Http\HttpResponse;
use PdfGate\Http\HttpTransportInterface;
use PdfGate\PdfGateClient;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class PdfGateClientTest extends TestCase
{
    public function testConstructorRejectsEmptyApiKey(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        new PdfGateClient('   ');
    }

    public function testGeneratePdfEnforcesJsonResponse(): void
    {
        $transport = new RecordingTransport(new HttpResponse(201, $this->successfulGenerateResponseBody()));
        $client = PdfGateClient::createWithTransport('test_key_123', $transport);

        $client->generatePdf(array('url' => 'https://example.com'));

        $request = $transport->lastRequest;
        self::assertNotNull($request);
        self::assertSame('/v1/generate/pdf', parse_url($request->url, PHP_URL_PATH));
        self::assertSame(true, $request->jsonBody['jsonResponse']);
        self::assertSame('Bearer test_key_123', $request->headers['Authorization']);
        self::assertSame('https://api-sandbox.pdfgate.com/v1/generate/pdf', $request->url);
    }

    public function testGeneratePdfReturnsTypedResponse(): void
    {
        $transport = new RecordingTransport(new HttpResponse(201, $this->successfulGenerateResponseBody()));
        $client = PdfGateClient::createWithTransport('test_key_123', $transport);

        $response = $client->generatePdf(array('html' => '<p>Hello</p>'));

        self::assertInstanceOf(PdfGateDocumentMetadata::class, $response);
        self::assertSame('6642381c5c61', $response->getId());
        self::assertSame('completed', $response->getStatus());
        self::assertSame('from_html', $response->getType());
        self::assertSame('https://api.pdfgate.com/file/open/token', $response->getFileUrl());
        self::assertSame(1620006, $response->getSize());
        self::assertSame('2024-02-13T15:56:12.607Z', $response->getCreatedAt());
        self::assertNull($response->getDerivedFrom());
    }

    public function testGeneratePdfAllowsMissingOptionalFileUrlInResponse(): void
    {
        $transport = new RecordingTransport(new HttpResponse(201, $this->successfulGenerateResponseWithoutFileUrlBody()));
        $client = PdfGateClient::createWithTransport('test_key_123', $transport);

        $response = $client->generatePdf(array('html' => '<p>Hello</p>'));

        self::assertSame(null, $response->getFileUrl());
    }

    public function testLiveApiKeyUsesProductionBaseUrl(): void
    {
        $transport = new RecordingTransport(new HttpResponse(201, $this->successfulGenerateResponseBody()));
        $client = PdfGateClient::createWithTransport('live_key_123', $transport);

        $client->generatePdf(array('html' => '<p>Prod</p>'));

        $request = $transport->lastRequest;
        self::assertNotNull($request);
        self::assertSame('https://api.pdfgate.com/v1/generate/pdf', $request->url);
    }

    public function testTestApiKeyUsesSandboxBaseUrl(): void
    {
        $transport = new RecordingTransport(new HttpResponse(201, $this->successfulGenerateResponseBody()));
        $client = PdfGateClient::createWithTransport('test_key_123', $transport);

        $client->generatePdf(array('html' => '<p>Sandbox</p>'));

        $request = $transport->lastRequest;
        self::assertNotNull($request);
        self::assertSame('https://api-sandbox.pdfgate.com/v1/generate/pdf', $request->url);
    }

    public function testConstructorRejectsUnknownApiKeyPrefix(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        new PdfGateClient('invalid_key_prefix');
    }

    public function testNon2xxResponsesThrowApiExceptionWithStatusAndTruncatedBody(): void
    {
        $body = str_repeat('x', 1500);
        $transport = new RecordingTransport(new HttpResponse(401, $body));
        $client = PdfGateClient::createWithTransport('test_invalid', $transport);

        try {
            $client->generatePdf(array('url' => 'https://example.com'));
            self::fail('Expected ApiException was not thrown.');
        } catch (ApiException $e) {
            self::assertSame(401, $e->getStatusCode());
            self::assertLessThanOrEqual(1027, strlen($e->getResponseBody()));
            self::assertStringEndsWith('...', $e->getResponseBody());
            self::assertStringContainsString($e->getResponseBody(), $e->getMessage());
        }
    }

    public function testTransportFailuresAreWrappedWithOriginalCause(): void
    {
        $previous = new RuntimeException('socket failure');
        $transport = new ThrowingTransport($previous);
        $client = PdfGateClient::createWithTransport('test_key_123', $transport);

        try {
            $client->generatePdf(array('html' => '<p>Test</p>'));
            self::fail('Expected TransportException was not thrown.');
        } catch (TransportException $e) {
            self::assertSame($previous, $e->getPrevious());
            self::assertStringContainsString('POST https://api-sandbox.pdfgate.com/v1/generate/pdf', $e->getMessage());
            self::assertStringContainsString('RuntimeException: socket failure', $e->getMessage());
        }
    }

    public function testGeneratePdfForwardsArrayFieldsVerbatimAndEnforcesJsonResponse(): void
    {
        $transport = new RecordingTransport(new HttpResponse(201, $this->successfulGenerateResponseBody()));
        $client = PdfGateClient::createWithTransport('test_key_123', $transport);

        $client->generatePdf(array(
            'html' => '<p>Hello</p>',
            'pageSizeType' => 'a4',
            'margin' => array('top' => '10px', 'bottom' => '12px', 'left' => '8px', 'right' => '8px'),
            'clickSelectorChainSetup' => array(
                'ignoreFailingChains' => true,
                'chains' => array(
                    array('selectors' => array('#cookieDialog')),
                    array('selectors' => array('.popupClose')),
                ),
            ),
            'metadata' => array('env' => 'test'),
        ));

        $request = $transport->lastRequest;
        self::assertNotNull($request);
        self::assertSame('<p>Hello</p>', $request->jsonBody['html']);
        self::assertSame('a4', $request->jsonBody['pageSizeType']);
        self::assertSame(
            array(
                'top' => '10px',
                'bottom' => '12px',
                'left' => '8px',
                'right' => '8px',
            ),
            $request->jsonBody['margin']
        );
        self::assertSame(array('env' => 'test'), $request->jsonBody['metadata']);
        self::assertSame(true, $request->jsonBody['jsonResponse']);
    }

    public function testFlattenPdfEnforcesJsonResponseAndUsesFlattenPath(): void
    {
        $transport = new RecordingTransport(new HttpResponse(201, $this->successfulFlattenResponseBody()));
        $client = PdfGateClient::createWithTransport('test_key_123', $transport);

        $client->flattenPdf(array('documentId' => '68f920bacfe16de217f019as'));

        $request = $transport->lastRequest;
        self::assertNotNull($request);
        self::assertSame('/forms/flatten', parse_url($request->url, PHP_URL_PATH));
        self::assertSame('68f920bacfe16de217f019as', $request->jsonBody['documentId']);
        self::assertSame(true, $request->jsonBody['jsonResponse']);
        self::assertSame('Bearer test_key_123', $request->headers['Authorization']);
        self::assertSame('https://api-sandbox.pdfgate.com/forms/flatten', $request->url);
    }

    public function testFlattenPdfReturnsTypedResponseWithDerivedFrom(): void
    {
        $transport = new RecordingTransport(new HttpResponse(201, $this->successfulFlattenResponseBody()));
        $client = PdfGateClient::createWithTransport('test_key_123', $transport);

        $response = $client->flattenPdf(array('documentId' => '68f920bacfe16de217f019as'));

        self::assertInstanceOf(PdfGateDocumentMetadata::class, $response);
        self::assertSame('6642381c5c61', $response->getId());
        self::assertSame('completed', $response->getStatus());
        self::assertSame('flattened', $response->getType());
        self::assertSame('https://api.pdfgate.com/file/open/token', $response->getFileUrl());
        self::assertSame(1620006, $response->getSize());
        self::assertSame('2024-02-13T15:56:12.607Z', $response->getCreatedAt());
        self::assertSame('68f920bacfe16de217f019as', $response->getDerivedFrom());
    }

    public function testFlattenPdfForwardsOptionalFieldsVerbatimAndEnforcesJsonResponse(): void
    {
        $transport = new RecordingTransport(new HttpResponse(201, $this->successfulFlattenResponseBody()));
        $client = PdfGateClient::createWithTransport('test_key_123', $transport);

        $client->flattenPdf(array(
            'documentId' => '68f920bacfe16de217f019as',
            'preSignedUrlExpiresIn' => 1200,
            'metadata' => array('env' => 'test'),
        ));

        $request = $transport->lastRequest;
        self::assertNotNull($request);
        self::assertSame('68f920bacfe16de217f019as', $request->jsonBody['documentId']);
        self::assertSame(1200, $request->jsonBody['preSignedUrlExpiresIn']);
        self::assertSame(array('env' => 'test'), $request->jsonBody['metadata']);
        self::assertSame(true, $request->jsonBody['jsonResponse']);
    }

    private function successfulGenerateResponseBody(): string
    {
        return '{"id":"6642381c5c61","status":"completed","type":"from_html","fileUrl":"https://api.pdfgate.com/file/open/token","size":1620006,"createdAt":"2024-02-13T15:56:12.607Z"}';
    }

    private function successfulGenerateResponseWithoutFileUrlBody(): string
    {
        return '{"id":"6642381c5c61","status":"completed","type":"from_html","size":1620006,"createdAt":"2024-02-13T15:56:12.607Z"}';
    }

    private function successfulFlattenResponseBody(): string
    {
        return '{"id":"6642381c5c61","status":"completed","type":"flattened","fileUrl":"https://api.pdfgate.com/file/open/token","size":1620006,"createdAt":"2024-02-13T15:56:12.607Z","derivedFrom":"68f920bacfe16de217f019as"}';
    }
}

final class RecordingTransport implements HttpTransportInterface
{
    /** @var HttpResponse */
    private $response;

    /** @var HttpRequest|null */
    public $lastRequest;

    public function __construct(HttpResponse $response)
    {
        $this->response = $response;
        $this->lastRequest = null;
    }

    public function send(HttpRequest $request): HttpResponse
    {
        $this->lastRequest = $request;

        return $this->response;
    }
}

final class ThrowingTransport implements HttpTransportInterface
{
    /** @var RuntimeException */
    private $exception;

    public function __construct(RuntimeException $exception)
    {
        $this->exception = $exception;
    }

    public function send(HttpRequest $request): HttpResponse
    {
        throw $this->exception;
    }
}
