<?php

declare(strict_types=1);

namespace PdfGate\Dto;

use DateTimeImmutable;
use Exception;
use PdfGate\Exception\TransportException;

/**
 * Stored recipient DTO returned by the recipient directory endpoints.
 */
class PdfGateRecipient
{
    /** @var string */
    private $id;

    /** @var string */
    private $email;

    /** @var string|null */
    private $name;

    /** @var array<string,mixed>|null */
    private $metadata;

    /** @var DateTimeImmutable|null */
    private $createdAt;

    /** @var DateTimeImmutable|null */
    private $updatedAt;

    /** @var DateTimeImmutable|null */
    private $lastUsedAt;

    /**
     * @param array<string,mixed>|null $metadata
     */
    public function __construct(
        string $id,
        string $email,
        ?string $name = null,
        ?array $metadata = null,
        ?DateTimeImmutable $createdAt = null,
        ?DateTimeImmutable $updatedAt = null,
        ?DateTimeImmutable $lastUsedAt = null
    ) {
        $this->id = $id;
        $this->email = $email;
        $this->name = $name;
        $this->metadata = $metadata;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
        $this->lastUsedAt = $lastUsedAt;
    }

    /**
     * @param array<string,mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        $required = array('id', 'email');

        foreach ($required as $field) {
            if (!array_key_exists($field, $payload)) {
                throw new TransportException(sprintf('Missing "%s" in recipient response.', $field));
            }
        }

        if (array_key_exists('metadata', $payload) && $payload['metadata'] !== null && !is_array($payload['metadata'])) {
            throw new TransportException('Expected "metadata" to be an object in recipient response.');
        }

        return new self(
            (string) $payload['id'],
            (string) $payload['email'],
            array_key_exists('name', $payload) && $payload['name'] !== null
                ? (string) $payload['name']
                : null,
            array_key_exists('metadata', $payload) ? $payload['metadata'] : null,
            self::parseOptionalDate($payload, 'createdAt'),
            self::parseOptionalDate($payload, 'updatedAt'),
            self::parseOptionalDate($payload, 'lastUsedAt')
        );
    }

    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Recipient email. Stored lowercased and cannot be changed after creation.
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @return array<string,mixed>|null
     */
    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * When the recipient was last referenced by an envelope.
     */
    public function getLastUsedAt(): ?DateTimeImmutable
    {
        return $this->lastUsedAt;
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
                sprintf('Invalid "%s" timestamp in recipient response.', $field)
            );
        }
    }
}
