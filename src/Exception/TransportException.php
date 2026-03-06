<?php

declare(strict_types=1);

namespace PdfGate\Exception;

use Throwable;

/**
 * Thrown when the HTTP transport fails before a response is received.
 */
class TransportException extends PdfGateException
{
    /**
     * Builds a transport exception preserving a root cause and copying its code.
     *
     * @param Throwable $previous Root cause to wrap.
     * @param string $message Exception message.
     */
    public static function causedBy(Throwable $previous, string $message): self
    {
        return new self($message, (int) $previous->getCode(), $previous);
    }

    /**
     * Builds a transport exception for request failures before a response is received.
     *
     * @param string $method HTTP method used in the failed request.
     * @param string $url Fully-qualified URL used in the failed request.
     * @param Throwable $previous Root cause from the underlying transport layer.
     */
    public static function forRequestFailure(string $method, string $url, Throwable $previous): self
    {
        return self::causedBy(
            $previous,
            sprintf(
                'Failed to complete request %s %s. Cause: %s: %s',
                $method,
                $url,
                get_class($previous),
                $previous->getMessage()
            )
        );
    }
}
