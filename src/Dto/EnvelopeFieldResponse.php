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

    /** @var string|null */
    private $timezone;

    /** @var string|null */
    private $source;

    /** @var string|null */
    private $userValue;

    /** @var string|null */
    private $userTimezone;

    /**
     * @param string $type One of the DocumentFieldType constants.
     * @param mixed|null $value
     */
    public function __construct(
        string $name,
        string $type,
        $value = null,
        ?bool $checked = null,
        ?string $timezone = null,
        ?string $source = null,
        ?string $userValue = null,
        ?string $userTimezone = null
    ) {
        $this->name = $name;
        $this->type = $type;
        $this->value = $value;
        $this->checked = $checked;
        $this->timezone = $timezone;
        $this->source = $source;
        $this->userValue = $userValue;
        $this->userTimezone = $userTimezone;
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
            array_key_exists('checked', $payload) ? (bool) $payload['checked'] : null,
            self::optionalString($payload, 'timezone'),
            self::optionalString($payload, 'source'),
            self::optionalString($payload, 'userValue'),
            self::optionalString($payload, 'userTimezone')
        );
    }

    /**
     * @param array<string,mixed> $payload
     */
    private static function optionalString(array $payload, string $key): ?string
    {
        if (!array_key_exists($key, $payload) || $payload[$key] === null) {
            return null;
        }

        return (string) $payload[$key];
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

    /**
     * IANA timezone of the stored value. For datetime fields the value is normalized to
     * UTC, so this is "UTC" once a value is captured.
     */
    public function getTimezone(): ?string
    {
        return $this->timezone;
    }

    /**
     * Where the value originated: "server" for auto-filled fields or "user" for values
     * submitted by the recipient.
     */
    public function getSource(): ?string
    {
        return $this->source;
    }

    /**
     * Original value as submitted by the recipient, before UTC normalization (datetime fields).
     */
    public function getUserValue(): ?string
    {
        return $this->userValue;
    }

    /**
     * IANA timezone the recipient submitted the value in (datetime fields).
     */
    public function getUserTimezone(): ?string
    {
        return $this->userTimezone;
    }
}
