<?php

declare(strict_types=1);

namespace PdfGate\Tests\Unit;

use PdfGate\Http\CurlClientInterface;
use PdfGate\Http\CurlHttpTransport;
use PdfGate\Http\HttpRequest;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class CurlHttpTransportTest extends TestCase
{
    public function testSendReturnsStatusCodeAndBody(): void
    {
        $curl = new FakeCurlClient();
        $curl->execResult = '{"ok":true}';
        $curl->infoResult = 201;

        $transport = new CurlHttpTransport($curl);

        $response = $transport->send(
            HttpRequest::makePostJson(
                'https://api.pdfgate.com/v1/generate/pdf',
                array('Authorization' => 'Bearer x'),
                null
            )
        );

        self::assertSame(201, $response->statusCode);
        self::assertSame('{"ok":true}', $response->body);
        self::assertArrayHasKey(CURLOPT_RETURNTRANSFER, $curl->setOptArrayOptions);
        self::assertSame(true, $curl->setOptArrayOptions[CURLOPT_RETURNTRANSFER]);
    }

    public function testSendAddsJsonContentTypeAndEncodedPayload(): void
    {
        $curl = new FakeCurlClient();
        $curl->execResult = '{}';
        $curl->infoResult = 200;

        $transport = new CurlHttpTransport($curl);

        $transport->send(
            HttpRequest::makePostJson(
                'https://api.pdfgate.com/v1/generate/pdf',
                array('Authorization' => 'Bearer x'),
                array('html' => '<p>Hello</p>')
            )
        );

        self::assertSame('{"html":"<p>Hello<\/p>"}', $curl->postFields);
        self::assertContains('Content-Type: application/json', $curl->setOptArrayOptions[CURLOPT_HTTPHEADER]);
    }

    public function testSendSupportsMultipartPayloadWithoutJsonContentType(): void
    {
        $curl = new FakeCurlClient();
        $curl->execResult = '{}';
        $curl->infoResult = 200;

        $transport = new CurlHttpTransport($curl);
        $multipartPayload = array(
            'documentId' => 'doc_123',
            'type' => 'image',
            'watermark' => new \CURLFile('/tmp/watermark.png'),
        );

        $transport->send(
            HttpRequest::makePostMultipart(
                'https://api.pdfgate.com/watermark/pdf',
                array('Authorization' => 'Bearer x'),
                $multipartPayload
            )
        );

        self::assertSame($multipartPayload, $curl->postFields);
        self::assertNotContains('Content-Type: application/json', $curl->setOptArrayOptions[CURLOPT_HTTPHEADER]);
    }

    public function testSendNormalizesMultipartBooleansAndNestedArrays(): void
    {
        $curl = new FakeCurlClient();
        $curl->execResult = '{}';
        $curl->infoResult = 200;

        $transport = new CurlHttpTransport($curl);

        $transport->send(
            HttpRequest::makePostMultipart(
                'https://api.pdfgate.com/watermark/pdf',
                array('Authorization' => 'Bearer x'),
                array(
                    'documentId' => 'doc_123',
                    'jsonResponse' => true,
                    'metadata' => array(
                        'suite' => 'acceptance',
                        'flags' => array('first' => false),
                    ),
                )
            )
        );

        self::assertSame('doc_123', $curl->postFields['documentId']);
        self::assertSame('true', $curl->postFields['jsonResponse']);
        self::assertSame('acceptance', $curl->postFields['metadata[suite]']);
        self::assertSame('false', $curl->postFields['metadata[flags][first]']);
    }

    public function testSendSupportsGetRequestWithoutBody(): void
    {
        $curl = new FakeCurlClient();
        $curl->execResult = '%PDF-1.7';
        $curl->infoResult = 200;

        $transport = new CurlHttpTransport($curl);

        $response = $transport->send(
            HttpRequest::makeGet(
                'https://api.pdfgate.com/file/doc_123',
                array('Authorization' => 'Bearer x')
            )
        );

        self::assertSame(200, $response->statusCode);
        self::assertSame('%PDF-1.7', $response->body);
        self::assertNull($curl->postFields);
        self::assertSame('GET', $curl->setOptArrayOptions[CURLOPT_CUSTOMREQUEST]);
        self::assertNotContains('Content-Type: application/json', $curl->setOptArrayOptions[CURLOPT_HTTPHEADER]);
    }

    public function testSendThrowsWhenCurlInitFails(): void
    {
        $curl = new FakeCurlClient();
        $curl->initResult = false;

        $transport = new CurlHttpTransport($curl);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to initialize cURL.');

        $transport->send(HttpRequest::makePostJson('https://api.pdfgate.com/v1/generate/pdf'));
    }

    public function testSendThrowsWhenJsonEncodingFails(): void
    {
        $curl = new FakeCurlClient();
        $curl->execResult = '{}';
        $curl->infoResult = 200;

        $transport = new CurlHttpTransport($curl);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to encode request JSON body.');

        $transport->send(
            HttpRequest::makePostJson(
                'https://api.pdfgate.com/v1/generate/pdf',
                array(),
                array('bad' => "\xB1\x31")
            )
        );
    }

    public function testSendThrowsWhenCurlExecFails(): void
    {
        $curl = new FakeCurlClient();
        $curl->execResult = false;
        $curl->errorResult = 'connection failed';

        $transport = new CurlHttpTransport($curl);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('cURL request failed: connection failed');

        $transport->send(HttpRequest::makePostJson('https://api.pdfgate.com/v1/generate/pdf'));
    }

    public function testSendThrowsWhenSettingPostFieldsFails(): void
    {
        $curl = new FakeCurlClient();
        $curl->setOptResult = false;

        $transport = new CurlHttpTransport($curl);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to set cURL option CURLOPT_POSTFIELDS.');

        $transport->send(
            HttpRequest::makePostJson(
                'https://api.pdfgate.com/v1/generate/pdf',
                array(),
                array('html' => '<p>Hello</p>')
            )
        );
    }

    public function testSendThrowsWhenSettingRequestOptionsFails(): void
    {
        $curl = new FakeCurlClient();
        $curl->setOptArrayResult = false;

        $transport = new CurlHttpTransport($curl);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to set cURL request options.');

        $transport->send(HttpRequest::makePostJson('https://api.pdfgate.com/v1/generate/pdf'));
    }

}

final class FakeCurlClient implements CurlClientInterface
{
    /** @var mixed */
    public $initResult = 'handle';

    /** @var string|false */
    public $execResult = '';

    /** @var mixed */
    public $infoResult = 200;

    /** @var string */
    public $errorResult = '';

    /** @var array<int,mixed> */
    public $setOptArrayOptions = array();

    /** @var mixed */
    public $postFields = null;

    /** @var bool */
    public $setOptResult = true;

    /** @var bool */
    public $setOptArrayResult = true;

    public function init()
    {
        return $this->initResult;
    }

    public function setOpt($handle, int $option, $value): bool
    {
        if ($option === CURLOPT_POSTFIELDS) {
            $this->postFields = $value;
        }

        return $this->setOptResult;
    }

    public function setOptArray($handle, array $options): bool
    {
        $this->setOptArrayOptions = $options;
        return $this->setOptArrayResult;
    }

    public function exec($handle)
    {
        return $this->execResult;
    }

    public function getInfo($handle, int $option)
    {
        return $this->infoResult;
    }

    public function error($handle): string
    {
        return $this->errorResult;
    }

    public function close($handle): void
    {
    }
}
