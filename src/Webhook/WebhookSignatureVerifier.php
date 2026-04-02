<?php

declare(strict_types=1);

namespace PdfGate\Webhook;

use PdfGate\Exception\InvalidArgumentException;
use PdfGate\Exception\SignatureVerificationException;

/**
 * Verifies PDFGate webhook signatures from the x-pdfgate-signature header.
 */
final class WebhookSignatureVerifier
{
    private const DEFAULT_TOLERANCE = 300;

    private function __construct()
    {
    }

    /**
     * @param string $secret Webhook signing secret.
     * @param string|null $signatureHeader Raw x-pdfgate-signature header value.
     * @param string $payload Raw request body exactly as received.
     * @throws InvalidArgumentException
     * @throws SignatureVerificationException
     */
    public static function verify(
        string $secret,
        ?string $signatureHeader,
        string $payload
    ): void {
        if (trim($secret) === '') {
            throw new InvalidArgumentException('Webhook secret cannot be empty.');
        }

        list($timestamp, $signatures) = self::parseSignatureHeader($signatureHeader);

        $now = time();
        if (abs($now - $timestamp) > self::DEFAULT_TOLERANCE) {
            throw new SignatureVerificationException('Signature expired.');
        }

        $expected = hash_hmac('sha256', $timestamp . '.' . $payload, $secret);

        foreach ($signatures as $signature) {
            if (hash_equals($expected, strtolower($signature))) {
                return;
            }
        }

        throw new SignatureVerificationException('Invalid signature.');
    }

    /**
     * @param string|null $signatureHeader
     * @return array{0:int,1:list<string>}
     */
    private static function parseSignatureHeader(?string $signatureHeader): array
    {
        if ($signatureHeader === null || trim($signatureHeader) === '') {
            throw new SignatureVerificationException('Missing signature.');
        }

        $timestamp = null;
        $signatures = array();
        $parts = explode(',', $signatureHeader);

        foreach ($parts as $part) {
            $pair = explode('=', trim($part), 2);
            if (count($pair) !== 2) {
                continue;
            }

            list($key, $value) = $pair;

            if ($key === 't' && ctype_digit($value)) {
                $timestamp = (int) $value;
                continue;
            }

            if ($key === 'v1' && $value !== '') {
                $signatures[] = $value;
            }
        }

        if ($timestamp === null) {
            throw new SignatureVerificationException('Missing timestamp.');
        }

        if ($signatures === array()) {
            throw new SignatureVerificationException('Missing signature.');
        }

        return array($timestamp, $signatures);
    }
}
