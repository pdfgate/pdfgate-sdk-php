<?php

declare(strict_types=1);

namespace PdfGate\Enum;

/**
 * Envelope lifecycle statuses returned by PDFGate.
 */
final class EnvelopeStatus
{
    public const CREATED = 'created';
    public const IN_PROGRESS = 'in_progress';
    public const COMPLETED = 'completed';
    public const EXPIRED = 'expired';

    private function __construct()
    {
    }
}
