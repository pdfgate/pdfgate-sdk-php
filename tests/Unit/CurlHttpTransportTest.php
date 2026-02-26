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
            new HttpRequest('POST', 'https://api.pdfgate.com/v1/generate/pdf', array('Authorization' => 'Bearer x'))
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
            new HttpRequest(
                'POST',
                'https://api.pdfgate.com/v1/generate/pdf',
                array('Authorization' => 'Bearer x'),
                array('html' => '<p>Hello</p>')
            )
        );

        self::assertSame('{"html":"<p>Hello<\/p>"}', $curl->postFields);
        self::assertContains('Content-Type: application/json', $curl->setOptArrayOptions[CURLOPT_HTTPHEADER]);
    }

    public function testSendThrowsWhenCurlInitFails(): void
    {
        $curl = new FakeCurlClient();
        $curl->initResult = false;

        $transport = new CurlHttpTransport($curl);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to initialize cURL.');

        $transport->send(new HttpRequest('GET', 'https://api.pdfgate.com/v1/generate/pdf'));
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
            new HttpRequest(
                'POST',
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

        $transport->send(new HttpRequest('GET', 'https://api.pdfgate.com/v1/generate/pdf'));
    }

    public function testSendThrowsWhenSettingPostFieldsFails(): void
    {
        $curl = new FakeCurlClient();
        $curl->setOptResult = false;

        $transport = new CurlHttpTransport($curl);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to set cURL option CURLOPT_POSTFIELDS.');

        $transport->send(
            new HttpRequest(
                'POST',
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

        $transport->send(new HttpRequest('GET', 'https://api.pdfgate.com/v1/generate/pdf'));
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

    /** @var string|null */
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
            $this->postFields = (string) $value;
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
