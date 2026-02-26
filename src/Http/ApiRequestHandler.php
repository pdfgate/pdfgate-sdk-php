<?php

declare(strict_types=1);

namespace PdfGate\Http;

use JsonException;
use PdfGate\Exception\ApiException;
use PdfGate\Exception\TransportException;
use Throwable;

/**
 * Handles authenticated API requests and response parsing.
 */
class ApiRequestHandler
{
    private const ERROR_BODY_LIMIT = 1024;

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
    public function postJsonResponse(string $path, array $payload): array
    {
        $url = (new UrlBuilder())
            ->withDomain($this->baseUrl)
            ->withPath($path)
            ->withQuery([])
            ->build();
        $request = HttpRequest::makePostJson(
            $url,
            $this->authHeaders(),
            $payload
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
    public function postMultipartJsonResponse(string $path, array $payload): array
    {
        $url = (new UrlBuilder())
            ->withDomain($this->baseUrl)
            ->withPath($path)
            ->withQuery([])
            ->build();
        $request = HttpRequest::makePostMultipart(
            $url,
            $this->authHeaders(),
            $payload
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
    public function getJsonResponse(string $path, array $query = array()): array
    {
        $url = (new UrlBuilder())
            ->withDomain($this->baseUrl)
            ->withPath($path)
            ->withQuery($query)
            ->build();
        $request = HttpRequest::makeGet(
            $url,
            $this->authHeaders()
        );

        $response = $this->send($request);

        return $this->decodeJsonResponse($response->body);
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

    /**
     * @param string $body
     * @return array<string,mixed>
     */
    private function decodeJsonResponse(string $body): array
    {
        try {
            $decoded = json_decode($body, false, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new TransportException('Failed to decode JSON response body.', 0, $e);
        }

        if (!is_object($decoded)) {
            throw new TransportException('Expected JSON object response body.');
        }

        /** @var array<string,mixed> $decodedArray */
        $decodedArray = get_object_vars($decoded);
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
            throw new TransportException(
                sprintf(
                    'Failed to complete request %s %s. Cause: %s: %s',
                    $request->method,
                    $request->url,
                    get_class($e),
                    $e->getMessage()
                ),
                (int) $e->getCode(),
                $e
            );
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
}
