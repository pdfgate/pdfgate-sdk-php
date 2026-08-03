<?php

declare(strict_types=1);

namespace PdfGate\Dto;

use PdfGate\Exception\TransportException;

/**
 * Webhook metadata DTO returned by PDFGate webhook endpoints.
 */
class WebhookResponse
{
    /** @var string */
    private $id;

    /** @var string */
    private $url;

    /** @var list<string> One of the WebhookEventType constants each. */
    private $eventTypes;

    /** @var string One of the WebhookStatus constants. */
    private $status;

    /** @var string|null */
    private $description;

    /** @var string|null */
    private $secret;

    /** @var string|null */
    private $createdAt;

    /** @var string|null */
    private $updatedAt;

    /**
     * @param list<string> $eventTypes One of the WebhookEventType constants each.
     * @param string $status One of the WebhookStatus constants.
     */
    public function __construct(
        string $id,
        string $url,
        array $eventTypes,
        string $status,
        ?string $description = null,
        ?string $secret = null,
        ?string $createdAt = null,
        ?string $updatedAt = null
    ) {
        $this->id = $id;
        $this->url = $url;
        $this->eventTypes = $eventTypes;
        $this->status = $status;
        $this->description = $description;
        $this->secret = $secret;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    /**
     * @param array<string,mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        $required = array('id', 'url', 'status');

        foreach ($required as $field) {
            if (!array_key_exists($field, $payload)) {
                throw new TransportException(sprintf('Missing "%s" in webhook response.', $field));
            }
        }

        $eventTypes = array();
        if (array_key_exists('eventTypes', $payload) && is_array($payload['eventTypes'])) {
            foreach ($payload['eventTypes'] as $eventType) {
                $eventTypes[] = (string) $eventType;
            }
        }

        return new self(
            (string) $payload['id'],
            (string) $payload['url'],
            $eventTypes,
            (string) $payload['status'],
            self::optionalString($payload, 'description'),
            self::optionalString($payload, 'secret'),
            self::optionalString($payload, 'createdAt'),
            self::optionalString($payload, 'updatedAt')
        );
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * @return list<string> One of the WebhookEventType constants each.
     */
    public function getEventTypes(): array
    {
        return $this->eventTypes;
    }

    /**
     * Returns one of the WebhookStatus constants.
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Signing secret. Only returned once, when the webhook is created.
     */
    public function getSecret(): ?string
    {
        return $this->secret;
    }

    public function getCreatedAt(): ?string
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?string
    {
        return $this->updatedAt;
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
}
