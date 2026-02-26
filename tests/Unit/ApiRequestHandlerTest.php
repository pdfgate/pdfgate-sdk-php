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
