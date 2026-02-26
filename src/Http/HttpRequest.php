<?php

declare(strict_types=1);

namespace PdfGate\Http;

/**
 * Immutable HTTP request used by transport implementations.
 */
class HttpRequest
{
    /** @var string */
    public $method;

    /** @var string */
    public $url;

    /** @var array<string,string> */
    public $headers;

    /** @var array<string,mixed>|null */
    public $jsonBody;

    /**
     * @param string $method HTTP method.
     * @param string $url Full request URL without query string.
     * @param array<string,string> $headers HTTP headers map.
     * @param array<string,mixed>|null $jsonBody JSON request body.
     */
    public function __construct(
        string $method,
        string $url,
        array $headers = array(),
        ?array $jsonBody = null
    ) {
        $this->method = $method;
        $this->url = $url;
        $this->headers = $headers;
        $this->jsonBody = $jsonBody;
    }
}
