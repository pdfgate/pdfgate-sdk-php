<?php

declare(strict_types=1);

namespace PdfGate\Exception;

/**
 * Thrown when PDFGate returns a non-2xx HTTP response.
 */
class ApiException extends PdfGateException
{
    /** @var int */
    private $statusCode;

    /** @var string */
    private $responseBody;

    /**
     * @param int $statusCode HTTP response status code.
     * @param string $responseBody Truncated response body.
     */
    public function __construct(int $statusCode, string $responseBody)
    {
        $this->statusCode = $statusCode;
        $this->responseBody = $responseBody;

        $message = sprintf(
            'PDFGate API request failed with status %d. Response body: %s',
            $statusCode,
            $responseBody
        );

        parent::__construct($message);
    }

    /**
     * Returns the HTTP status code returned by API.
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Returns the truncated response body from API.
     */
    public function getResponseBody(): string
    {
        return $this->responseBody;
    }
}
