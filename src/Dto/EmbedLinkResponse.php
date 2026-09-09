<?php

declare(strict_types=1);

namespace PdfGate\Dto;

use DateTimeImmutable;
use Exception;
use PdfGate\Exception\TransportException;

/**
 * Embed link DTO returned by the create embed link endpoint.
 */
class EmbedLinkResponse
{
    /** @var string */
    private $url;

    /** @var DateTimeImmutable When the embed link stops working. */
    private $expiresAt;

    public function __construct(string $url, DateTimeImmutable $expiresAt)
    {
        $this->url = $url;
        $this->expiresAt = $expiresAt;
    }

    /**
     * @param array<string,mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        $required = array('url', 'expiresAt');

        foreach ($required as $field) {
            if (!array_key_exists($field, $payload)) {
                throw new TransportException(sprintf('Missing "%s" in embed link response.', $field));
            }
        }

        try {
            $expiresAt = new DateTimeImmutable((string) $payload['expiresAt']);
        } catch (Exception $e) {
            throw TransportException::causedBy(
                $e,
                'Invalid "expiresAt" timestamp in embed link response.'
            );
        }

        return new self((string) $payload['url'], $expiresAt);
    }

    /**
     * URL to render in an iframe so the embedded recipient can sign.
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * When the embed link stops working.
     */
    public function getExpiresAt(): DateTimeImmutable
    {
        return $this->expiresAt;
    }
}
