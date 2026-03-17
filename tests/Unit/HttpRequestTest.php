<?php

declare(strict_types=1);

namespace PdfGate\Tests\Unit;

use PdfGate\Http\HttpRequest;
use PHPUnit\Framework\TestCase;
use LogicException;

final class HttpRequestTest extends TestCase
{
    public function testMakeGetSetsNoBody(): void
    {
        $request = HttpRequest::makeGet(
            'https://api.pdfgate.com/file/doc_123',
            array('Authorization' => 'Bearer x'),
            60
        );

        self::assertSame('GET', $request->getMethod());
        self::assertSame('https://api.pdfgate.com/file/doc_123', $request->getUrl());
        self::assertSame(array('Authorization' => 'Bearer x'), $request->getHeaders());
        self::assertNull($request->getJsonBody());
        self::assertNull($request->getMultipartBody());
        self::assertSame(60, $request->getTimeout());
    }

    public function testMakePostJsonSetsJsonBodyOnly(): void
    {
        $request = HttpRequest::makePostJson(
            'https://api.pdfgate.com/watermark/pdf',
            array('Authorization' => 'Bearer x'),
            array('jsonResponse' => true),
            180
        );

        self::assertSame('POST', $request->getMethod());
        self::assertSame('https://api.pdfgate.com/watermark/pdf', $request->getUrl());
        self::assertSame(array('Authorization' => 'Bearer x'), $request->getHeaders());
        self::assertSame(array('jsonResponse' => true), $request->getJsonBody());
        self::assertNull($request->getMultipartBody());
        self::assertSame(180, $request->getTimeout());
    }

    public function testMakePostMultipartSetsMultipartBodyOnly(): void
    {
        $request = HttpRequest::makePostMultipart(
            'https://api.pdfgate.com/watermark/pdf',
            array('Authorization' => 'Bearer x'),
            array('documentId' => 'doc_123'),
            180
        );

        self::assertSame('POST', $request->getMethod());
        self::assertSame('https://api.pdfgate.com/watermark/pdf', $request->getUrl());
        self::assertSame(array('Authorization' => 'Bearer x'), $request->getHeaders());
        self::assertSame(array('documentId' => 'doc_123'), $request->getMultipartBody());
        self::assertNull($request->getJsonBody());
        self::assertSame(180, $request->getTimeout());
    }

    public function testMakeGetSetsNoRequestBody(): void
    {
        $request = HttpRequest::makeGet(
            'https://api.pdfgate.com/document/doc_123?preSignedUrlExpiresIn=1200',
            array('Authorization' => 'Bearer x'),
            60
        );

        self::assertSame('GET', $request->getMethod());
        self::assertSame('https://api.pdfgate.com/document/doc_123?preSignedUrlExpiresIn=1200', $request->getUrl());
        self::assertSame(array('Authorization' => 'Bearer x'), $request->getHeaders());
        self::assertNull($request->getJsonBody());
        self::assertNull($request->getMultipartBody());
        self::assertSame(60, $request->getTimeout());
    }

    public function testRequestCannotBeMutatedFromOutside(): void
    {
        $request = HttpRequest::makePostJson('https://api.pdfgate.com/v1/generate/pdf');

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('HttpRequest is immutable.');

        $request->__set('jsonBody', array('html' => '<p>Changed</p>'));
    }
}
