<?php

declare(strict_types=1);

namespace PdfGate\Enum;

/**
 * Recipient statuses returned inside an envelope document response.
 */
final class DocumentRecipientStatus
{
    public const PENDING = 'pending';
    public const EXPIRED = 'expired';
    public const VOIDED = 'voided';
    public const SIGNED = 'signed';

    private function __construct()
    {
    }
}
