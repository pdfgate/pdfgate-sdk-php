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

        $handler->postJsonResponse('/v1/generate/pdf', array('html' => '<p>Hello</p>'));
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

        $handler->postJsonResponse('/v1/generate/pdf', array('html' => '<p>Hello</p>'));
    }

    public function testPostJsonResponseAcceptsEmptyJsonObject(): void
    {
        $handler = new ApiRequestHandler(
            'https://api.pdfgate.com',
            'test_key_123',
            new StaticResponseTransport(new HttpResponse(200, '{}'))
        );

        $result = $handler->postJsonResponse('/v1/generate/pdf', array('html' => '<p>Hello</p>'));

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
            $handler->postJsonResponse('/v1/generate/pdf', array('html' => '<p>Hello</p>'));
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

        $handler->postMultipartJsonResponse('/watermark/pdf', array(
            'documentId' => 'source_123',
            'type' => 'text',
            'text' => 'watermark',
            'jsonResponse' => true,
        ));

        $request = $transport->lastRequest;
        self::assertNotNull($request);
        self::assertSame('POST', $request->method);
        self::assertSame('https://api.pdfgate.com/watermark/pdf', $request->url);
        self::assertSame('Bearer test_key_123', $request->headers['Authorization']);
        self::assertSame(null, $request->jsonBody);
        self::assertSame('source_123', $request->multipartBody['documentId']);
        self::assertSame('text', $request->multipartBody['type']);
        self::assertSame('watermark', $request->multipartBody['text']);
        self::assertSame(true, $request->multipartBody['jsonResponse']);
    }

    public function testPostMultipartJsonResponseDecodesJsonObjectResponse(): void
    {
        $handler = new ApiRequestHandler(
            'https://api.pdfgate.com',
            'test_key_123',
            new StaticResponseTransport(new HttpResponse(200, '{"id":"6642381c5c61","status":"completed"}'))
        );

        $result = $handler->postMultipartJsonResponse('/watermark/pdf', array(
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

        $handler->postMultipartJsonResponse('/upload', array(
            'file' => $file,
            'jsonResponse' => true,
        ));

        $request = $transport->lastRequest;
        self::assertNotNull($request);
        self::assertSame('POST', $request->method);
        self::assertSame('https://api.pdfgate.com/upload', $request->url);
        self::assertNull($request->jsonBody);
        self::assertSame($file, $request->multipartBody['file']);
        self::assertSame(true, $request->multipartBody['jsonResponse']);
    }

    public function testGetJsonResponseSendsGetRequestWithQueryAndAuthHeader(): void
    {
        $transport = new RecordingResponseTransport(new HttpResponse(200, '{"id":"doc_123"}'));
        $handler = new ApiRequestHandler(
            'https://api.pdfgate.com',
            'test_key_123',
            $transport
        );

        $handler->getJsonResponse('/document/doc_123', array('preSignedUrlExpiresIn' => 1200));

        $request = $transport->lastRequest;
        self::assertNotNull($request);
        self::assertSame('GET', $request->method);
        self::assertSame(
            'https://api.pdfgate.com/document/doc_123?preSignedUrlExpiresIn=1200',
            $request->url
        );
        self::assertSame('Bearer test_key_123', $request->headers['Authorization']);
        self::assertSame(null, $request->jsonBody);
        self::assertSame(null, $request->multipartBody);
    }

    public function testGetJsonResponseDecodesJsonObjectResponse(): void
    {
        $handler = new ApiRequestHandler(
            'https://api.pdfgate.com',
            'test_key_123',
            new StaticResponseTransport(new HttpResponse(200, '{"id":"6642381c5c61","status":"completed"}'))
        );

        $result = $handler->getJsonResponse('/document/6642381c5c61');

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

        $result = $handler->getBinaryResponse('/file/doc_123');

        self::assertSame('%PDF-1.7 binary', $result);
        $request = $transport->lastRequest;
        self::assertNotNull($request);
        self::assertSame('GET', $request->method);
        self::assertSame('https://api.pdfgate.com/file/doc_123', $request->url);
        self::assertSame('Bearer test_key_123', $request->headers['Authorization']);
        self::assertNull($request->jsonBody);
        self::assertNull($request->multipartBody);
    }

    public function testGetBinaryResponseThrowsApiExceptionOnNonSuccessStatusCode(): void
    {
        $handler = new ApiRequestHandler(
            'https://api.pdfgate.com',
            'test_key_123',
            new StaticResponseTransport(new HttpResponse(404, '{"error":"not found"}'))
        );

        try {
            $handler->getBinaryResponse('/file/missing_doc');
            self::fail('Expected ApiException was not thrown.');
        } catch (ApiException $e) {
            self::assertSame(404, $e->getStatusCode());
            self::assertStringContainsString('not found', $e->getMessage());
        }
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
