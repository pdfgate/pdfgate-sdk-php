<?php

declare(strict_types=1);

namespace PdfGate\Dto;

use DateTimeImmutable;
use Exception;
use PdfGate\Enum\SignatureAdoptionMethod;
use PdfGate\Enum\StoredSignatureStatus;
use PdfGate\Exception\TransportException;

/**
 * Automatic signing. The reusable signature a recipient stored through the
 * enrolment link, or the state of the enrolment.
 */
class RecipientStoredSignature
{
    /** @var string One of the StoredSignatureStatus constants. */
    private $status;

    /** @var DateTimeImmutable|null */
    private $requestedAt;

    /** @var string|null One of the SignatureAdoptionMethod constants. */
    private $adoptionMethod;

    /** @var DateTimeImmutable|null */
    private $enrolledAt;

    /** @var DateTimeImmutable|null */
    private $revokedAt;

    /** @var DateTimeImmutable|null */
    private $lastUsedAt;

    /** @var int */
    private $useCount;

    /** @var string|null */
    private $image;

    /**
     * @param string $status One of the StoredSignatureStatus constants.
     * @param string|null $adoptionMethod One of the SignatureAdoptionMethod constants.
     */
    public function __construct(
        string $status,
        ?DateTimeImmutable $requestedAt = null,
        ?string $adoptionMethod = null,
        ?DateTimeImmutable $enrolledAt = null,
        ?DateTimeImmutable $revokedAt = null,
        ?DateTimeImmutable $lastUsedAt = null,
        int $useCount = 0,
        ?string $image = null
    ) {
        $this->status = $status;
        $this->requestedAt = $requestedAt;
        $this->adoptionMethod = $adoptionMethod;
        $this->enrolledAt = $enrolledAt;
        $this->revokedAt = $revokedAt;
        $this->lastUsedAt = $lastUsedAt;
        $this->useCount = $useCount;
        $this->image = $image;
    }

    /**
     * @param array<string,mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        if (!array_key_exists('status', $payload)) {
            throw new TransportException('Missing "status" in recipient stored signature response.');
        }

        return new self(
            (string) $payload['status'],
            self::parseOptionalDate($payload, 'requestedAt'),
            array_key_exists('adoptionMethod', $payload) && $payload['adoptionMethod'] !== null
                ? (string) $payload['adoptionMethod']
                : null,
            self::parseOptionalDate($payload, 'enrolledAt'),
            self::parseOptionalDate($payload, 'revokedAt'),
            self::parseOptionalDate($payload, 'lastUsedAt'),
            array_key_exists('useCount', $payload) && $payload['useCount'] !== null
                ? (int) $payload['useCount']
                : 0,
            array_key_exists('image', $payload) && $payload['image'] !== null
                ? (string) $payload['image']
                : null
        );
    }

    /**
     * Returns one of the StoredSignatureStatus constants: pending while the
     * enrolment link was sent but not completed, active once the signature
     * can be applied, revoked after it was removed.
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * When the enrolment link was last sent.
     */
    public function getRequestedAt(): ?DateTimeImmutable
    {
        return $this->requestedAt;
    }

    /**
     * How the recipient created the signature. Returns one of the
     * SignatureAdoptionMethod constants, or null until the enrolment is
     * completed.
     */
    public function getAdoptionMethod(): ?string
    {
        return $this->adoptionMethod;
    }

    /**
     * When the recipient completed the enrolment.
     */
    public function getEnrolledAt(): ?DateTimeImmutable
    {
        return $this->enrolledAt;
    }

    /**
     * When the signature was removed.
     */
    public function getRevokedAt(): ?DateTimeImmutable
    {
        return $this->revokedAt;
    }

    /**
     * When the signature was last applied to a document.
     */
    public function getLastUsedAt(): ?DateTimeImmutable
    {
        return $this->lastUsedAt;
    }

    /**
     * How many documents the signature was applied to.
     */
    public function getUseCount(): int
    {
        return $this->useCount;
    }

    /**
     * The signature as a PNG data URL (data:image/png;base64,...). Returned
     * only by getRecipient() while the signature is active; null otherwise.
     */
    public function getImage(): ?string
    {
        return $this->image;
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
                sprintf('Invalid "%s" timestamp in recipient stored signature response.', $field)
            );
        }
    }
}
