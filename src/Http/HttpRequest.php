<?php

declare(strict_types=1);

namespace PdfGate\Http;

use LogicException;

/**
 * Immutable HTTP request used by transport implementations.
 */
class HttpRequest
{
    /** @var string */
    private $method;

    /** @var string */
    private $url;

    /** @var array<string,string> */
    private $headers;

    /** @var array<string,mixed>|null */
    private $jsonBody;

    /** @var array<string,mixed>|null */
    private $multipartBody;

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

    /**
     * @param string $url Full request URL including optional query string.
     * @param array<string,string> $headers HTTP headers map.
     */
    public static function makeGet(
        string $url,
        array $headers = array()
    ): self {
        return new self('GET', $url, $headers, null, null);
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * @return array<string,string>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * @return array<string,mixed>|null
     */
    public function getJsonBody(): ?array
    {
        return $this->jsonBody;
    }

    /**
     * @return array<string,mixed>|null
     */
    public function getMultipartBody(): ?array
    {
        return $this->multipartBody;
    }

    /**
     * @param mixed $value
     */
    public function __set(string $name, $value): void
    {
        throw new LogicException('HttpRequest is immutable.');
    }

    public function __unset(string $name): void
    {
        throw new LogicException('HttpRequest is immutable.');
    }
}
