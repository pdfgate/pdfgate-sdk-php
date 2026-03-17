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

    /** @var int */
    private $timeout;

    /**
     * @param string $method HTTP method.
     * @param string $url Full request URL without query string.
     * @param array<string,string> $headers HTTP headers map.
     * @param array<string,mixed>|null $jsonBody JSON request body.
     * @param array<string,mixed>|null $multipartBody multipart/form-data request body.
     * @param int $timeout Total request timeout in seconds.
     */
    private function __construct(
        string $method,
        string $url,
        array $headers = array(),
        ?array $jsonBody = null,
        ?array $multipartBody = null,
        int $timeout = 60
    ) {
        $this->method = $method;
        $this->url = $url;
        $this->headers = $headers;
        $this->jsonBody = $jsonBody;
        $this->multipartBody = $multipartBody;
        $this->timeout = $timeout;
    }

    /**
     * @param string $url Full request URL without query string.
     * @param array<string,string> $headers HTTP headers map.
     * @param array<string,mixed>|null $jsonBody JSON request body.
     * @param int $timeout Total request timeout in seconds.
     */
    public static function makePostJson(
        string $url,
        array $headers = array(),
        ?array $jsonBody = null,
        int $timeout = 60
    ): self {
        return new self('POST', $url, $headers, $jsonBody, null, $timeout);
    }

    /**
     * @param string $url Full request URL without query string.
     * @param array<string,string> $headers HTTP headers map.
     * @param array<string,mixed>|null $multipartBody multipart/form-data request body.
     * @param int $timeout Total request timeout in seconds.
     */
    public static function makePostMultipart(
        string $url,
        array $headers = array(),
        ?array $multipartBody = null,
        int $timeout = 60
    ): self {
        return new self('POST', $url, $headers, null, $multipartBody, $timeout);
    }

    /**
     * @param string $url Full request URL including optional query string.
     * @param array<string,string> $headers HTTP headers map.
     * @param int $timeout Total request timeout in seconds.
     */
    public static function makeGet(
        string $url,
        array $headers = array(),
        int $timeout = 60
    ): self {
        return new self('GET', $url, $headers, null, null, $timeout);
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

    public function getTimeout(): int
    {
        return $this->timeout;
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
