<?php

declare(strict_types=1);

namespace PdfGate\Tests\Unit;

use InvalidArgumentException;
use PdfGate\Http\UrlBuilder;
use PHPUnit\Framework\TestCase;

final class UrlBuilderTest extends TestCase
{
    public function testBuildJoinsDomainAndPath(): void
    {
        $url = (new UrlBuilder())
            ->withDomain('https://api.pdfgate.com/')
            ->withPath('/v1/generate/pdf')
            ->build();

        self::assertSame('https://api.pdfgate.com/v1/generate/pdf', $url);
    }

    public function testBuildAppendsQueryStringWithRfc3986Encoding(): void
    {
        $url = (new UrlBuilder())
            ->withDomain('https://api.pdfgate.com')
            ->withPath('/v1/generate/pdf')
            ->withQuery(array('q' => 'hello world', 'page' => 1))
            ->build();

        self::assertSame('https://api.pdfgate.com/v1/generate/pdf?q=hello%20world&page=1', $url);
    }

    public function testBuildRejectsPathWithoutLeadingSlash(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new UrlBuilder())
            ->withDomain('https://api.pdfgate.com')
            ->withPath('v1/generate/pdf')
            ->build();
    }

    public function testBuildRejectsPathContainingCrLf(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new UrlBuilder())
            ->withDomain('https://api.pdfgate.com')
            ->withPath("/v1/generate/pdf\r\nx")
            ->build();
    }
}
