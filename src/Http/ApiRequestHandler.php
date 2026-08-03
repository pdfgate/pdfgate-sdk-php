<?php

declare(strict_types=1);

namespace PdfGate\Http;

use JsonException;
use PdfGate\Exception\ApiException;
use PdfGate\Exception\TransportException;
use Throwable;

/**
 * Handles authenticated API requests with response parsing and error handling.
 */
class ApiRequestHandler
{
    private const ERROR_BODY_LIMIT = 1024;
    private const DEFAULT_TIMEOUT = 60;
    private const TIMEOUTS_BY_PATH = array(
        '/v1/generate/pdf' => 900,
        '/protect/pdf' => 180,
        '/watermark/pdf' => 180,
        '/compress/pdf' => 180,
        '/forms/flatten' => 180,
    );

    /** @var string */
    private $baseUrl;

    /** @var string */
    private $apiKey;

    /** @var HttpTransportInterface */
    private $transport;

    /**
     * @param string $baseUrl API base URL.
     * @param string $apiKey PDFGate API key.
     * @param HttpTransportInterface $transport HTTP transport implementation.
     */
    public function __construct(string $baseUrl, string $apiKey, HttpTransportInterface $transport)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->apiKey = $apiKey;
        $this->transport = $transport;
    }

    /**
     * Sends a POST request and parses a JSON response.
     *
     * @param string $path Endpoint path.
     * @param array<string,mixed> $payload Request body payload.
     * @return array<string,mixed>
     */
    public function postJson(string $path, array $payload): array
    {
        $payload = $this->normalizeArray($payload);
        $url = (new UrlBuilder())
            ->withDomain($this->baseUrl)
            ->withPath($path)
            ->build();
        $request = HttpRequest::makePostJson(
            $url,
            $this->authHeaders(),
            $payload,
            $this->resolveTimeoutForPath($path)
        );

        $response = $this->send($request);

        return $this->decodeJsonResponse($response->body);
    }

    /**
     * Sends a multipart/form-data POST request and parses a JSON response.
     *
     * @param string $path Endpoint path.
     * @param array<string,mixed> $payload multipart/form-data payload.
     * @return array<string,mixed>
     */
    public function postMultipart(string $path, array $payload): array
    {
        $payload = $this->normalizeArray($payload);
        $url = (new UrlBuilder())
            ->withDomain($this->baseUrl)
            ->withPath($path)
            ->build();
        $request = HttpRequest::makePostMultipart(
            $url,
            $this->authHeaders(),
            $payload,
            $this->resolveTimeoutForPath($path)
        );

        $response = $this->send($request);

        return $this->decodeJsonResponse($response->body);
    }

    /**
     * Sends a GET request and parses a JSON response.
     *
     * @param string $path Endpoint path.
     * @param array<string,mixed> $query Query string parameters.
     * @return array<string,mixed>
     */
    public function getJson(string $path, array $query = array()): array
    {
        $query = $this->normalizeArray($query);
        $url = (new UrlBuilder())
            ->withDomain($this->baseUrl)
            ->withPath($path)
            ->withQuery($query)
            ->build();
        $request = HttpRequest::makeGet(
            $url,
            $this->authHeaders(),
            $this->resolveTimeoutForPath($path)
        );

        $response = $this->send($request);

        return $this->decodeJsonResponse($response->body);
    }

    /**
     * Sends a DELETE request. Succeeds on any 2xx response (including 204 No Content).
     *
     * @param string $path Endpoint path.
     */
    public function delete(string $path): void
    {
        $url = (new UrlBuilder())
            ->withDomain($this->baseUrl)
            ->withPath($path)
            ->build();
        $request = HttpRequest::makeDelete(
            $url,
            $this->authHeaders(),
            $this->resolveTimeoutForPath($path)
        );

        $this->send($request);
    }

    /**
     * Sends a GET request and returns the raw response body.
     *
     * @param string $path Endpoint path.
     * @return string
     */
    public function getBinary(string $path): string
    {
        $url = (new UrlBuilder())
            ->withDomain($this->baseUrl)
            ->withPath($path)
            ->build();
        $request = HttpRequest::makeGet(
            $url,
            $this->authHeaders(),
            $this->resolveTimeoutForPath($path)
        );

        $response = $this->send($request);

        return $response->body;
    }

    /**
     * @return array<string,string>
     */
    private function authHeaders(): array
    {
        return array(
            'Authorization' => 'Bearer ' . $this->apiKey,
        );
    }

    private function resolveTimeoutForPath(string $path): int
    {
        return self::TIMEOUTS_BY_PATH[$path] ?? self::DEFAULT_TIMEOUT;
    }

    /**
     * @param string $body
     * @return array<string,mixed>
     */
    private function decodeJsonResponse(string $body): array
    {
        try {
            $decoded = json_decode($body, false, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw TransportException::causedBy($e, 'Failed to decode JSON response body.');
        }

        if (!is_object($decoded)) {
            throw new TransportException('Expected JSON object response body.');
        }

        /** @var array<string,mixed> $decodedArray */
        $decodedArray = $this->convertObjectToArray($decoded);
        return $decodedArray;
    }

    /**
     * @param HttpRequest $request
     * @return HttpResponse
     */
    private function send(HttpRequest $request): HttpResponse
    {
        try {
            $response = $this->transport->send($request);
        } catch (Throwable $e) {
            throw TransportException::forRequestFailure($request->getMethod(), $request->getUrl(), $e);
        }

        if ($response->statusCode < 200 || $response->statusCode >= 300) {
            throw new ApiException(
                $response->statusCode,
                $this->truncateBody($response->body)
            );
        }

        return $response;
    }

    private function truncateBody(string $body): string
    {
        if (strlen($body) <= self::ERROR_BODY_LIMIT) {
            return $body;
        }

        return substr($body, 0, self::ERROR_BODY_LIMIT) . '...';
    }

    /**
     * @param array<string,mixed> $payload
     * @return array<string,mixed>
     */
    private function normalizeArray(array $payload): array
    {
        $normalized = array();

        foreach ($payload as $key => $value) {
            if ($value === null) {
                continue;
            }

            if (is_array($value)) {
                $normalized[$key] = $this->normalizeArray($value);
                continue;
            }

            if (is_object($value) && !$value instanceof \CURLFile) {
                /** @var array<string,mixed> $fields */
                $fields = get_object_vars($value);
                $normalized[$key] = $this->normalizeArray($fields);
                continue;
            }

            $normalized[$key] = $value;
        }

        return $normalized;
    }

    /**
     * @param object $decoded
     * @return array<string,mixed>
     */
    private function convertObjectToArray(object $decoded): array
    {
        /** @var array<string,mixed> $fields */
        $fields = get_object_vars($decoded);
        $normalized = array();

        foreach ($fields as $key => $value) {
            $normalized[$key] = $this->convertJsonValue($value);
        }

        return $normalized;
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    private function convertJsonValue($value)
    {
        if (is_object($value)) {
            return $this->convertObjectToArray($value);
        }

        if (is_array($value)) {
            $normalized = array();

            foreach ($value as $key => $childValue) {
                $normalized[$key] = $this->convertJsonValue($childValue);
            }

            return $normalized;
        }

        return $value;
    }
}
