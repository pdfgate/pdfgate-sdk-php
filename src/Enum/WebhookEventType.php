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
    public const ENVELOPE_VOIDED = 'envelope.voided';
    public const ENVELOPE_DELETED = 'envelope.deleted';
    public const ENVELOPE_RECIPIENT_SIGNED = 'envelope.recipient.signed';

    /**
     * Occurs when it becomes a recipient's turn to sign on a document with a
     * signing order. Fires for every recipient, including those activated at
     * send. Payload: sourceDocumentId, recipientId, activatedAt.
     */
    public const ENVELOPE_RECIPIENT_ACTIVATED = 'envelope.recipient.activated';
    public const ENVELOPE_DOCUMENT_COMPLETED = 'envelope.document.completed';

    private function __construct()
    {
    }
}
