<?php

declare(strict_types=1);

namespace PdfGate\Enum;

/**
 * Supported envelope field types returned by PDFGate.
 */
final class DocumentFieldType
{
    public const SIGNATURE = 'signature';
    public const TEXT = 'text';
    public const NUMBER = 'number';
    public const TEXT_AREA = 'textarea';
    public const DATE = 'date';
    public const TIME = 'time';
    public const DATETIME = 'datetime';
    public const CHECKBOX = 'checkbox';
    public const RADIO_BUTTON = 'radio';
    public const SELECT = 'select';

    private function __construct()
    {
    }
}
