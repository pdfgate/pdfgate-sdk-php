<?php

declare(strict_types=1);

namespace PdfGate\Enum;

/**
 * How a recipient created their stored signature (automatic signing).
 */
final class SignatureAdoptionMethod
{
    public const TYPE_SIGNATURE = 'type_signature';
    public const DRAW_SIGNATURE = 'draw_signature';
    public const UPLOAD_SIGNATURE = 'upload_signature';

    private function __construct()
    {
    }
}
