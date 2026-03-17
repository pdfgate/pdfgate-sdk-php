<?php

declare(strict_types=1);

namespace PdfGate\Tests\Unit;

use PdfGate\Exception\ApiException;
use PdfGate\Exception\TransportException;
use PdfGate\Http\ApiRequestHandler;
use PdfGate\Http\HttpRequest;
use PdfGate\Http\HttpResponse;
use PdfGate\Http\HttpTransportInterface;
use PHPUnit\Framework\TestCase;

final class ApiRequestHandlerTest extends TestCase
{
    public function testPostJsonResponseThrowsWhenJsonCannotBeDecoded(): void
    {
        $handler = new ApiRequestHandler(
            'https://api.pdfgate.com',
            'test_key_123',
            new StaticResponseTransport(new HttpResponse(200, '{invalid-json'))
        );

        $this->expectException(TransportException::class);
        $this->expectExceptionMessage('Failed to decode JSON response body.');

        $handler->postJson('/v1/generate/pdf', array('html' => '<p>Hello</p>'));
    }

    public function testPostJsonResponseThrowsWhenJsonIsNotAnObject(): void
    {
        $handler = new ApiRequestHandler(
            'https://api.pdfgate.com',
            'test_key_123',
            new StaticResponseTransport(new HttpResponse(200, '["not","an","object"]'))
        );

        $this->expectException(TransportException::class);
        $this->expectExceptionMessage('Expected JSON object response body.');

        $handler->postJson('/v1/generate/pdf', array('html' => '<p>Hello</p>'));
    }

    public function testPostJsonResponseAcceptsEmptyJsonObject(): void
    {
        $handler = new ApiRequestHandler(
            'https://api.pdfgate.com',
            'test_key_123',
            new StaticResponseTransport(new HttpResponse(200, '{}'))
        );

        $result = $handler->postJson('/v1/generate/pdf', array('html' => '<p>Hello</p>'));

        self::assertSame(array(), $result);
    }

    public function testPostJsonResponseThrowsApiExceptionOnNonSuccessStatusCode(): void
    {
        $handler = new ApiRequestHandler(
            'https://api.pdfgate.com',
            'test_key_123',
            new StaticResponseTransport(new HttpResponse(400, '{"error":"bad request"}'))
        );

        try {
            $handler->postJson('/v1/generate/pdf', array('html' => '<p>Hello</p>'));
            self::fail('Expected ApiException was not thrown.');
        } catch (ApiException $e) {
            self::assertSame(400, $e->getStatusCode());
            self::assertStringContainsString('bad request', $e->getMessage());
        }
    }

    public function testPostMultipartJsonResponseSendsMultipartPayloadAndAuthHeader(): void
    {
        $transport = new RecordingResponseTransport(new HttpResponse(200, '{"id":"doc_123"}'));
        $handler = new ApiRequestHandler(
            'https://api.pdfgate.com',
            'test_key_123',
            $transport
        );

        $handler->postMultipart('/watermark/pdf', array(
            'documentId' => 'source_123',
            'type' => 'text',
            'text' => 'watermark',
            'jsonResponse' => true,
        ));

        $request = $transport->lastRequest;
        self::assertNotNull($request);
        self::assertSame('POST', $request->getMethod());
        self::assertSame('https://api.pdfgate.com/watermark/pdf', $request->getUrl());
        self::assertSame('Bearer test_key_123', $request->getHeaders()['Authorization']);
        self::assertSame(null, $request->getJsonBody());
        self::assertSame('source_123', $request->getMultipartBody()['documentId']);
        self::assertSame('text', $request->getMultipartBody()['type']);
        self::assertSame('watermark', $request->getMultipartBody()['text']);
        self::assertSame(true, $request->getMultipartBody()['jsonResponse']);
        self::assertSame(180, $request->getTimeout());
    }

    public function testPostMultipartJsonResponseDecodesJsonObjectResponse(): void
    {
        $handler = new ApiRequestHandler(
            'https://api.pdfgate.com',
            'test_key_123',
            new StaticResponseTransport(new HttpResponse(200, '{"id":"6642381c5c61","status":"completed"}'))
        );

        $result = $handler->postMultipart('/watermark/pdf', array(
            'documentId' => 'source_123',
            'type' => 'text',
            'text' => 'watermark',
        ));

        self::assertSame('6642381c5c61', $result['id']);
        self::assertSame('completed', $result['status']);
    }

    public function testPostMultipartJsonResponseSupportsUploadEndpoint(): void
    {
        $transport = new RecordingResponseTransport(new HttpResponse(200, '{"id":"doc_upload_123","status":"completed"}'));
        $handler = new ApiRequestHandler(
            'https://api.pdfgate.com',
            'test_key_123',
            $transport
        );
        $file = new \CURLFile(__FILE__, 'application/pdf', 'upload.pdf');

        $handler->postMultipart('/upload', array(
            'file' => $file,
            'jsonResponse' => true,
        ));

        $request = $transport->lastRequest;
        self::assertNotNull($request);
        self::assertSame('POST', $request->getMethod());
        self::assertSame('https://api.pdfgate.com/upload', $request->getUrl());
        self::assertNull($request->getJsonBody());
        self::assertSame($file, $request->getMultipartBody()['file']);
        self::assertSame(true, $request->getMultipartBody()['jsonResponse']);
        self::assertSame(60, $request->getTimeout());
    }

    public function testGetJsonResponseSendsGetRequestWithQueryAndAuthHeader(): void
    {
        $transport = new RecordingResponseTransport(new HttpResponse(200, '{"id":"doc_123"}'));
        $handler = new ApiRequestHandler(
            'https://api.pdfgate.com',
            'test_key_123',
            $transport
        );

        $handler->getJson('/document/doc_123', array('preSignedUrlExpiresIn' => 1200));

        $request = $transport->lastRequest;
        self::assertNotNull($request);
        self::assertSame('GET', $request->getMethod());
        self::assertSame(
            'https://api.pdfgate.com/document/doc_123?preSignedUrlExpiresIn=1200',
            $request->getUrl()
        );
        self::assertSame('Bearer test_key_123', $request->getHeaders()['Authorization']);
        self::assertSame(null, $request->getJsonBody());
        self::assertSame(null, $request->getMultipartBody());
        self::assertSame(60, $request->getTimeout());
    }

    public function testGetJsonResponseDecodesJsonObjectResponse(): void
    {
        $handler = new ApiRequestHandler(
            'https://api.pdfgate.com',
            'test_key_123',
            new StaticResponseTransport(new HttpResponse(200, '{"id":"6642381c5c61","status":"completed"}'))
        );

        $result = $handler->getJson('/document/6642381c5c61');

        self::assertSame('6642381c5c61', $result['id']);
        self::assertSame('completed', $result['status']);
    }

    public function testGetBinaryResponseSendsGetRequestAndReturnsRawBody(): void
    {
        $transport = new RecordingResponseTransport(new HttpResponse(200, '%PDF-1.7 binary'));
        $handler = new ApiRequestHandler(
            'https://api.pdfgate.com',
            'test_key_123',
            $transport
        );

        $result = $handler->getBinary('/file/doc_123');

        self::assertSame('%PDF-1.7 binary', $result);
        $request = $transport->lastRequest;
        self::assertNotNull($request);
        self::assertSame('GET', $request->getMethod());
        self::assertSame('https://api.pdfgate.com/file/doc_123', $request->getUrl());
        self::assertSame('Bearer test_key_123', $request->getHeaders()['Authorization']);
        self::assertNull($request->getJsonBody());
        self::assertNull($request->getMultipartBody());
        self::assertSame(60, $request->getTimeout());
    }

    public function testGetBinaryResponseThrowsApiExceptionOnNonSuccessStatusCode(): void
    {
        $handler = new ApiRequestHandler(
            'https://api.pdfgate.com',
            'test_key_123',
            new StaticResponseTransport(new HttpResponse(404, '{"error":"not found"}'))
        );

        try {
            $handler->getBinary('/file/missing_doc');
            self::fail('Expected ApiException was not thrown.');
        } catch (ApiException $e) {
            self::assertSame(404, $e->getStatusCode());
            self::assertStringContainsString('not found', $e->getMessage());
        }
    }

    public function testPostJsonGenerateRequestUsesExtendedTimeout(): void
    {
        $transport = new RecordingResponseTransport(new HttpResponse(200, '{"id":"doc_123"}'));
        $handler = new ApiRequestHandler(
            'https://api.pdfgate.com',
            'test_key_123',
            $transport
        );

        $handler->postJson('/v1/generate/pdf', array('html' => '<p>Hello</p>'));

        self::assertSame(900, $transport->lastRequest->getTimeout());
    }

    public function testPostJsonProtectRequestUsesMediumTimeout(): void
    {
        $transport = new RecordingResponseTransport(new HttpResponse(200, '{"id":"doc_123"}'));
        $handler = new ApiRequestHandler(
            'https://api.pdfgate.com',
            'test_key_123',
            $transport
        );

        $handler->postJson('/protect/pdf', array('documentId' => 'doc_123'));

        self::assertSame(180, $transport->lastRequest->getTimeout());
    }

    public function testPostJsonCompressRequestUsesMediumTimeout(): void
    {
        $transport = new RecordingResponseTransport(new HttpResponse(200, '{"id":"doc_123"}'));
        $handler = new ApiRequestHandler(
            'https://api.pdfgate.com',
            'test_key_123',
            $transport
        );

        $handler->postJson('/compress/pdf', array('documentId' => 'doc_123'));

        self::assertSame(180, $transport->lastRequest->getTimeout());
    }

    public function testPostJsonFlattenRequestUsesMediumTimeout(): void
    {
        $transport = new RecordingResponseTransport(new HttpResponse(200, '{"id":"doc_123"}'));
        $handler = new ApiRequestHandler(
            'https://api.pdfgate.com',
            'test_key_123',
            $transport
        );

        $handler->postJson('/forms/flatten', array('documentId' => 'doc_123'));

        self::assertSame(180, $transport->lastRequest->getTimeout());
    }

    public function testPostJsonUsesDefaultTimeoutForOtherEndpoints(): void
    {
        $transport = new RecordingResponseTransport(new HttpResponse(200, '{"id":"doc_123"}'));
        $handler = new ApiRequestHandler(
            'https://api.pdfgate.com',
            'test_key_123',
            $transport
        );

        $handler->postJson('/forms/extract-data', array('documentId' => 'doc_123'));

        self::assertSame(60, $transport->lastRequest->getTimeout());
    }
}

final class StaticResponseTransport implements HttpTransportInterface
{
    /** @var HttpResponse */
    private $response;

    public function __construct(HttpResponse $response)
    {
        $this->response = $response;
    }

    public function send(HttpRequest $request): HttpResponse
    {
        return $this->response;
    }
}

final class RecordingResponseTransport implements HttpTransportInterface
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
