<?php

declare(strict_types=1);

namespace PdfGate\Dto;

use PdfGate\Enum\DocumentFieldType;
use PdfGate\Exception\TransportException;

/**
 * Field payload returned for an envelope recipient.
 */
class EnvelopeFieldResponse
{
    /** @var string */
    private $name;

    /** @var string One of the DocumentFieldType constants. */
    private $type;

    /** @var mixed|null */
    private $value;

    /** @var bool|null */
    private $checked;

    /**
     * @param string $type One of the DocumentFieldType constants.
     * @param mixed|null $value
     */
    public function __construct(string $name, string $type, $value = null, ?bool $checked = null)
    {
        $this->name = $name;
        $this->type = $type;
        $this->value = $value;
        $this->checked = $checked;
    }

    /**
     * @param array<string,mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        $required = array('name', 'type');

        foreach ($required as $field) {
            if (!array_key_exists($field, $payload)) {
                throw new TransportException(sprintf('Missing "%s" in envelope field response.', $field));
            }
        }

        return new self(
            (string) $payload['name'],
            (string) $payload['type'],
            $payload['value'] ?? null,
            array_key_exists('checked', $payload) ? (bool) $payload['checked'] : null
        );
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Returns one of the DocumentFieldType constants.
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return mixed|null
     */
    public function getValue()
    {
        return $this->value;
    }

    public function isChecked(): ?bool
    {
        return $this->checked;
    }
}
