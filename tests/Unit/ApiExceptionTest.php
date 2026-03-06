<?php

declare(strict_types=1);

namespace PdfGate\Tests\Unit;

use PdfGate\Exception\ApiException;
use PHPUnit\Framework\TestCase;

final class ApiExceptionTest extends TestCase
{
    public function testConstructorBuildsMessageFromStatusAndResponseBody(): void
    {
        $exception = new ApiException(422, '{"error":"invalid input"}');

        self::assertSame(422, $exception->getStatusCode());
        self::assertSame('{"error":"invalid input"}', $exception->getResponseBody());
        self::assertSame(
            'PDFGate API request failed with status 422. Response body: {"error":"invalid input"}',
            $exception->getMessage()
        );
    }
}
