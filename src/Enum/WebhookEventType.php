<?php

declare(strict_types=1);

namespace PdfGate\Enum;

/**
 * Events that a webhook can subscribe to.
 */
final class WebhookEventType
{
    public const ENVELOPE_SENT = 'envelope.sent';
    public const ENVELOPE_COMPLETED = 'envelope.completed';
    public const ENVELOPE_EXPIRED = 'envelope.expired';
    public const ENVELOPE_DOCUMENT_COMPLETED = 'envelope.document.completed';

    private function __construct()
    {
    }
}
