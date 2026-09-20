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

    /** @var string|null */
    private $signingLink;

    /** @var string|null */
    private $previewLink;

    /** @var string|null */
    private $recipientId;

    /** @var bool Whether the recipient signs through embedded signing. */
    private $embedded;

    /** @var int|null Signing order of the recipient, starting from 1. */
    private $signingOrder;

    /** @var DateTimeImmutable|null */
    private $activatedAt;

    /**
     * @param list<EnvelopeFieldResponse> $fields
     * @param string $status One of the DocumentRecipientStatus constants.
     */
    public function __construct(
        string $email,
        string $status,
        ?DateTimeImmutable $signedAt,
        ?DateTimeImmutable $viewedAt,
        array $fields,
        ?string $signingLink = null,
        ?string $previewLink = null,
        ?string $recipientId = null,
        bool $embedded = false,
        ?int $signingOrder = null,
        ?DateTimeImmutable $activatedAt = null
    ) {
        $this->email = $email;
        $this->status = $status;
        $this->signedAt = $signedAt;
        $this->viewedAt = $viewedAt;
        $this->fields = $fields;
        $this->signingLink = $signingLink;
        $this->previewLink = $previewLink;
        $this->recipientId = $recipientId;
        $this->embedded = $embedded;
        $this->signingOrder = $signingOrder;
        $this->activatedAt = $activatedAt;
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
            $fields,
            array_key_exists('signingLink', $payload) && $payload['signingLink'] !== null
                ? (string) $payload['signingLink']
                : null,
            array_key_exists('previewLink', $payload) && $payload['previewLink'] !== null
                ? (string) $payload['previewLink']
                : null,
            array_key_exists('recipientId', $payload) && $payload['recipientId'] !== null
                ? (string) $payload['recipientId']
                : null,
            array_key_exists('embedded', $payload) ? (bool) $payload['embedded'] : false,
            array_key_exists('signingOrder', $payload) && $payload['signingOrder'] !== null
                ? (int) $payload['signingOrder']
                : null,
            self::parseOptionalDate($payload, 'activatedAt', 'envelope recipient response')
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
     * Link the recipient uses to sign. Present while the recipient still needs to sign.
     */
    public function getSigningLink(): ?string
    {
        return $this->signingLink;
    }

    /**
     * Link to preview the signed document. Present once the recipient has signed.
     */
    public function getPreviewLink(): ?string
    {
        return $this->previewLink;
    }

    /**
     * ID of the stored recipient. Present when the recipient references an
     * entry in the recipient directory.
     */
    /**
     * Whether the recipient signs through embedded signing. Embedded
     * recipients receive no emails and have no signing link.
     */
    public function isEmbedded(): bool
    {
        return $this->embedded;
    }

    public function getRecipientId(): ?string
    {
        return $this->recipientId;
    }

    /**
     * Signing order of the recipient, starting from 1. Recipients sign one
     * after another in this order and a recipient is activated once everyone
     * with a lower value has signed. Recipients with the same value can sign
     * in parallel. Null when the document has no signing order.
     */
    public function getSigningOrder(): ?int
    {
        return $this->signingOrder;
    }

    /**
     * The time it became the recipient's turn to sign. Null until the
     * recipient is activated.
     */
    public function getActivatedAt(): ?DateTimeImmutable
    {
        return $this->activatedAt;
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
