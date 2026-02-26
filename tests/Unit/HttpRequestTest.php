<?php

declare(strict_types=1);

namespace PdfGate\Tests\Unit;

use PdfGate\Http\HttpRequest;
use PHPUnit\Framework\TestCase;

final class HttpRequestTest extends TestCase
{
    public function testMakeGetSetsNoBody(): void
    {
        $request = HttpRequest::makeGet(
            'https://api.pdfgate.com/file/doc_123',
            array('Authorization' => 'Bearer x')
        );

        self::assertSame('GET', $request->method);
        self::assertSame('https://api.pdfgate.com/file/doc_123', $request->url);
        self::assertSame(array('Authorization' => 'Bearer x'), $request->headers);
        self::assertNull($request->jsonBody);
        self::assertNull($request->multipartBody);
    }

    public function testMakePostJsonSetsJsonBodyOnly(): void
    {
        $request = HttpRequest::makePostJson(
            'https://api.pdfgate.com/watermark/pdf',
            array('Authorization' => 'Bearer x'),
            array('jsonResponse' => true)
        );

        self::assertSame('POST', $request->method);
        self::assertSame('https://api.pdfgate.com/watermark/pdf', $request->url);
        self::assertSame(array('Authorization' => 'Bearer x'), $request->headers);
        self::assertSame(array('jsonResponse' => true), $request->jsonBody);
        self::assertNull($request->multipartBody);
    }

    public function testMakePostMultipartSetsMultipartBodyOnly(): void
    {
        $request = HttpRequest::makePostMultipart(
            'https://api.pdfgate.com/watermark/pdf',
            array('Authorization' => 'Bearer x'),
            array('documentId' => 'doc_123')
        );

        self::assertSame('POST', $request->method);
        self::assertSame('https://api.pdfgate.com/watermark/pdf', $request->url);
        self::assertSame(array('Authorization' => 'Bearer x'), $request->headers);
        self::assertSame(array('documentId' => 'doc_123'), $request->multipartBody);
        self::assertNull($request->jsonBody);
    }

    public function testMakeGetSetsNoRequestBody(): void
    {
        $request = HttpRequest::makeGet(
            'https://api.pdfgate.com/document/doc_123?preSignedUrlExpiresIn=1200',
            array('Authorization' => 'Bearer x')
        );

        self::assertSame('GET', $request->method);
        self::assertSame('https://api.pdfgate.com/document/doc_123?preSignedUrlExpiresIn=1200', $request->url);
        self::assertSame(array('Authorization' => 'Bearer x'), $request->headers);
        self::assertNull($request->jsonBody);
        self::assertNull($request->multipartBody);
    }
}
