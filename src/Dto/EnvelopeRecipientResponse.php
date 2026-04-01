<?php

declare(strict_types=1);

namespace PdfGate\Dto;

use DateTimeImmutable;
use Exception;
use PdfGate\Enum\DocumentRecipientStatus;
use PdfGate\Exception\TransportException;

/**
 * Recipient payload returned for an envelope document.
 */
class EnvelopeRecipientResponse
{
    /** @var string */
    private $email;

    /** @var string One of the DocumentRecipientStatus constants. */
    private $status;

    /** @var DateTimeImmutable|null */
    private $signedAt;

    /** @var DateTimeImmutable|null */
    private $viewedAt;

    /** @var list<EnvelopeFieldResponse> */
    private $fields;

    /**
     * @param list<EnvelopeFieldResponse> $fields
     * @param string $status One of the DocumentRecipientStatus constants.
     */
    public function __construct(
        string $email,
        string $status,
        ?DateTimeImmutable $signedAt,
        ?DateTimeImmutable $viewedAt,
        array $fields
    ) {
        $this->email = $email;
        $this->status = $status;
        $this->signedAt = $signedAt;
        $this->viewedAt = $viewedAt;
        $this->fields = $fields;
    }

    /**
     * @param array<string,mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        $required = array('email', 'status', 'fields');

        foreach ($required as $field) {
            if (!array_key_exists($field, $payload)) {
                throw new TransportException(sprintf('Missing "%s" in envelope recipient response.', $field));
            }
        }

        if (!is_array($payload['fields'])) {
            throw new TransportException('Expected "fields" to be an array in envelope recipient response.');
        }

        $fields = array();
        foreach ($payload['fields'] as $fieldPayload) {
            if (!is_array($fieldPayload)) {
                throw new TransportException('Expected each envelope field response to be an object.');
            }

            $fields[] = EnvelopeFieldResponse::fromArray($fieldPayload);
        }

        return new self(
            (string) $payload['email'],
            (string) $payload['status'],
            self::parseOptionalDate($payload, 'signedAt', 'envelope recipient response'),
            self::parseOptionalDate($payload, 'viewedAt', 'envelope recipient response'),
            $fields
        );
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * Returns one of the DocumentRecipientStatus constants.
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    public function getSignedAt(): ?DateTimeImmutable
    {
        return $this->signedAt;
    }

    public function getViewedAt(): ?DateTimeImmutable
    {
        return $this->viewedAt;
    }

    /**
     * @return list<EnvelopeFieldResponse>
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    /**
     * @param array<string,mixed> $payload
     */
    private static function parseOptionalDate(array $payload, string $field, string $context): ?DateTimeImmutable
    {
        if (!array_key_exists($field, $payload) || $payload[$field] === null) {
            return null;
        }

        try {
            return new DateTimeImmutable((string) $payload[$field]);
        } catch (Exception $e) {
            throw TransportException::causedBy(
                $e,
                sprintf('Invalid "%s" timestamp in %s.', $field, $context)
            );
        }
    }
}
