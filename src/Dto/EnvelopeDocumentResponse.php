<?php

declare(strict_types=1);

namespace PdfGate\Dto;

use DateTimeImmutable;
use Exception;
use PdfGate\Enum\EnvelopeDocumentStatus;
use PdfGate\Exception\TransportException;

/**
 * Document payload returned inside an envelope response.
 */
class EnvelopeDocumentResponse
{
    /** @var string */
    private $sourceDocumentId;

    /** @var string|null */
    private $signedDocumentId;

    /** @var list<EnvelopeRecipientResponse> */
    private $recipients;

    /** @var string One of the EnvelopeDocumentStatus constants. */
    private $status;

    /** @var DateTimeImmutable|null */
    private $completedAt;

    /**
     * @param list<EnvelopeRecipientResponse> $recipients
     * @param string $status One of the EnvelopeDocumentStatus constants.
     */
    public function __construct(
        string $sourceDocumentId,
        ?string $signedDocumentId,
        array $recipients,
        string $status,
        ?DateTimeImmutable $completedAt
    ) {
        $this->sourceDocumentId = $sourceDocumentId;
        $this->signedDocumentId = $signedDocumentId;
        $this->recipients = $recipients;
        $this->status = $status;
        $this->completedAt = $completedAt;
    }

    /**
     * @param array<string,mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        $required = array('sourceDocumentId', 'recipients', 'status');

        foreach ($required as $field) {
            if (!array_key_exists($field, $payload)) {
                throw new TransportException(sprintf('Missing "%s" in envelope document response.', $field));
            }
        }

        if (!is_array($payload['recipients'])) {
            throw new TransportException('Expected "recipients" to be an array in envelope document response.');
        }

        $recipients = array();
        foreach ($payload['recipients'] as $recipientPayload) {
            if (!is_array($recipientPayload)) {
                throw new TransportException('Expected each envelope recipient response to be an object.');
            }

            $recipients[] = EnvelopeRecipientResponse::fromArray($recipientPayload);
        }

        return new self(
            (string) $payload['sourceDocumentId'],
            array_key_exists('signedDocumentId', $payload) ? (string) $payload['signedDocumentId'] : null,
            $recipients,
            (string) $payload['status'],
            self::parseOptionalDate($payload, 'completedAt')
        );
    }

    public function getSourceDocumentId(): string
    {
        return $this->sourceDocumentId;
    }

    public function getSignedDocumentId(): ?string
    {
        return $this->signedDocumentId;
    }

    /**
     * @return list<EnvelopeRecipientResponse>
     */
    public function getRecipients(): array
    {
        return $this->recipients;
    }

    /**
     * Returns one of the EnvelopeDocumentStatus constants.
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    public function getCompletedAt(): ?DateTimeImmutable
    {
        return $this->completedAt;
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
                sprintf('Invalid "%s" timestamp in envelope document response.', $field)
            );
        }
    }
}
