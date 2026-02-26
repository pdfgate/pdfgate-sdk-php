<?php

declare(strict_types=1);

namespace PdfGate\Http;

/**
 * Immutable HTTP response returned by transport implementations.
 */
class HttpResponse
{
    /** @var int */
    public $statusCode;

    /** @var string */
    public $body;

    /** @var array<string,string> */
    public $headers;

    /**
     * @param int $statusCode HTTP response status code.
     * @param string $body Raw response body.
     * @param array<string,string> $headers HTTP response headers.
     */
    public function __construct(int $statusCode, string $body, array $headers = array())
    {
        $this->statusCode = $statusCode;
        $this->body = $body;
        $this->headers = $headers;
    }
}
