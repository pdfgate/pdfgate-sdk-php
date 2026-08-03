<?php

declare(strict_types=1);

namespace PdfGate\Enum;

/**
 * Webhook statuses returned by PDFGate.
 */
final class WebhookStatus
{
    public const ACTIVE = 'active';
    public const DISABLED = 'disabled';

    private function __construct()
    {
    }
}
