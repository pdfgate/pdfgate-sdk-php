<?php

declare(strict_types=1);

namespace PdfGate\Exception;

use Throwable;

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
     * @param string $message Exception message.
     * @param Throwable|null $previous Wrapped root cause.
     */
    public function __construct(int $statusCode, string $responseBody, string $message = '', ?Throwable $previous = null)
    {
        $this->statusCode = $statusCode;
        $this->responseBody = $responseBody;

        if ($message === '') {
            $message = sprintf(
                'PDFGate API request failed with status %d. Response body: %s',
                $statusCode,
                $responseBody
            );
        } else {
            $message = sprintf('%s Response body: %s', rtrim($message, '.'), $responseBody);
        }

        parent::__construct($message, 0, $previous);
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
