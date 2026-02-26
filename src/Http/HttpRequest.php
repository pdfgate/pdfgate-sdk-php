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

    /** @var array<string,mixed>|null */
    public $multipartBody;

    /**
     * @param string $method HTTP method.
     * @param string $url Full request URL without query string.
     * @param array<string,string> $headers HTTP headers map.
     * @param array<string,mixed>|null $jsonBody JSON request body.
     * @param array<string,mixed>|null $multipartBody multipart/form-data request body.
     */
    private function __construct(
        string $method,
        string $url,
        array $headers = array(),
        ?array $jsonBody = null,
        ?array $multipartBody = null
    ) {
        $this->method = $method;
        $this->url = $url;
        $this->headers = $headers;
        $this->jsonBody = $jsonBody;
        $this->multipartBody = $multipartBody;
    }

    /**
     * @param string $url Full request URL without query string.
     * @param array<string,string> $headers HTTP headers map.
     * @param array<string,mixed>|null $jsonBody JSON request body.
     */
    public static function makePostJson(
        string $url,
        array $headers = array(),
        ?array $jsonBody = null
    ): self {
        return new self('POST', $url, $headers, $jsonBody, null);
    }

    /**
     * @param string $url Full request URL without query string.
     * @param array<string,string> $headers HTTP headers map.
     * @param array<string,mixed>|null $multipartBody multipart/form-data request body.
     */
    public static function makePostMultipart(
        string $url,
        array $headers = array(),
        ?array $multipartBody = null
    ): self {
        return new self('POST', $url, $headers, null, $multipartBody);
    }
}
