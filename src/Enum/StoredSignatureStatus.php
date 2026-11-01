<?php

declare(strict_types=1);

namespace PdfGate\Enum;

/**
 * State of a recipient's stored signature (automatic signing).
 */
final class StoredSignatureStatus
{
    /** The enrolment link was sent but the recipient has not completed it. */
    public const PENDING = 'pending';

    /** The signature can be applied to documents. */
    public const ACTIVE = 'active';

    /** The signature was removed. */
    public const REVOKED = 'revoked';

    private function __construct()
    {
    }
}
