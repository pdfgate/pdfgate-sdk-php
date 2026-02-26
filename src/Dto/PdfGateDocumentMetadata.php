<?php

declare(strict_types=1);

namespace PdfGate\Dto;

use PdfGate\Exception\TransportException;

/**
 * Document metadata DTO returned by JSON PDFGate endpoints.
 */
class PdfGateDocumentMetadata
{
    /** @var string */
    private $id;

    /** @var string */
    private $status;

    /** @var string */
    private $type;

    /** @var string|null */
    private $fileUrl;

    /** @var int */
    private $size;

    /** @var string */
    private $createdAt;

    /** @var string|null */
    private $derivedFrom;

    public function __construct(
        string $id,
        string $status,
        string $type,
        ?string $fileUrl,
        int $size,
        string $createdAt,
        ?string $derivedFrom = null
    ) {
        $this->id = $id;
        $this->status = $status;
        $this->type = $type;
        $this->fileUrl = $fileUrl;
        $this->size = $size;
        $this->createdAt = $createdAt;
        $this->derivedFrom = $derivedFrom;
    }

    /**
     * @param array<string,mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        $required = array('id', 'status', 'type', 'size', 'createdAt');

        foreach ($required as $field) {
            if (!array_key_exists($field, $payload)) {
                throw new TransportException(sprintf('Missing "%s" in document metadata response.', $field));
            }
        }

        return new self(
            (string) $payload['id'],
            (string) $payload['status'],
            (string) $payload['type'],
            array_key_exists('fileUrl', $payload) ? (string) $payload['fileUrl'] : null,
            (int) $payload['size'],
            (string) $payload['createdAt'],
            array_key_exists('derivedFrom', $payload) ? (string) $payload['derivedFrom'] : null
        );
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getFileUrl(): ?string
    {
        return $this->fileUrl;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    public function getDerivedFrom(): ?string
    {
        return $this->derivedFrom;
    }
}
