<?php

declare(strict_types=1);

namespace PdfGate\Dto;

use DateTimeImmutable;
use Exception;
use PdfGate\Enum\EnvelopeStatus;
use PdfGate\Exception\TransportException;

/**
 * Envelope DTO returned by the create envelope endpoint.
 */
class PdfGateEnvelope
{
    /** @var string */
    private $id;

    /** @var string One of the EnvelopeStatus constants. */
    private $status;

    /** @var list<EnvelopeDocumentResponse> */
    private $documents;

    /** @var DateTimeImmutable */
    private $createdAt;

    /** @var DateTimeImmutable|null */
    private $completedAt;

    /** @var DateTimeImmutable|null */
    private $expiredAt;

    /** @var array<string,mixed>|null */
    private $metadata;

    /**
     * @param list<EnvelopeDocumentResponse> $documents
     * @param array<string,mixed>|null $metadata
     * @param string $status One of the EnvelopeStatus constants.
     */
    public function __construct(
        string $id,
        string $status,
        array $documents,
        DateTimeImmutable $createdAt,
        ?DateTimeImmutable $completedAt = null,
        ?DateTimeImmutable $expiredAt = null,
        ?array $metadata = null
    ) {
        $this->id = $id;
        $this->status = $status;
        $this->documents = $documents;
        $this->createdAt = $createdAt;
        $this->completedAt = $completedAt;
        $this->expiredAt = $expiredAt;
        $this->metadata = $metadata;
    }

    /**
     * @param array<string,mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        $required = array('id', 'status', 'documents', 'createdAt');

        foreach ($required as $field) {
            if (!array_key_exists($field, $payload)) {
                throw new TransportException(sprintf('Missing "%s" in envelope response.', $field));
            }
        }

        if (!is_array($payload['documents'])) {
            throw new TransportException('Expected "documents" to be an array in envelope response.');
        }

        $documents = array();
        foreach ($payload['documents'] as $documentPayload) {
            if (!is_array($documentPayload)) {
                throw new TransportException('Expected each envelope document response to be an object.');
            }

            $documents[] = EnvelopeDocumentResponse::fromArray($documentPayload);
        }

        if (array_key_exists('metadata', $payload) && $payload['metadata'] !== null && !is_array($payload['metadata'])) {
            throw new TransportException('Expected "metadata" to be an object in envelope response.');
        }

        return new self(
            (string) $payload['id'],
            (string) $payload['status'],
            $documents,
            self::parseRequiredDate($payload, 'createdAt'),
            self::parseOptionalDate($payload, 'completedAt'),
            self::parseOptionalDate($payload, 'expiredAt'),
            array_key_exists('metadata', $payload) ? $payload['metadata'] : null
        );
    }

    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Returns one of the EnvelopeStatus constants.
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @return list<EnvelopeDocumentResponse>
     */
    public function getDocuments(): array
    {
        return $this->documents;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getCompletedAt(): ?DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function getExpiredAt(): ?DateTimeImmutable
    {
        return $this->expiredAt;
    }

    /**
     * @return array<string,mixed>|null
     */
    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    /**
     * @param array<string,mixed> $payload
     */
    private static function parseRequiredDate(array $payload, string $field): DateTimeImmutable
    {
        try {
            return new DateTimeImmutable((string) $payload[$field]);
        } catch (Exception $e) {
            throw TransportException::causedBy(
                $e,
                sprintf('Invalid "%s" timestamp in envelope response.', $field)
            );
        }
    }

    /**
     * @param array<string,mixed> $payload
     */
    private static function parseOptionalDate(array $payload, string $field): ?DateTimeImmutable
    {
        if (!array_key_exists($field, $payload) || $payload[$field] === null) {
            return null;
        }

        try {
            return new DateTimeImmutable((string) $payload[$field]);
        } catch (Exception $e) {
            throw TransportException::causedBy(
                $e,
                sprintf('Invalid "%s" timestamp in envelope response.', $field)
            );
        }
    }
}
