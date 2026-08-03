<?php

declare(strict_types=1);

namespace PdfGate\Enum;

/**
 * Per-document statuses returned inside an envelope response.
 */
final class EnvelopeDocumentStatus
{
    public const PENDING = 'pending';
    public const EXPIRED = 'expired';
    public const SENT_FOR_SIGNING = 'sent_for_signing';
    public const SIGNING_IN_PROGRESS = 'signing_in_progress';
    public const SIGNING_FAILED = 'signing_failed';
    public const COMPLETED = 'completed';

    private function __construct()
    {
    }
}
