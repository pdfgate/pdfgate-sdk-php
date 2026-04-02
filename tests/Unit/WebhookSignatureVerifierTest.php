<?php

declare(strict_types=1);

namespace PdfGate\Tests\Unit;

use PdfGate\Exception\SignatureVerificationException;
use PdfGate\Webhook\WebhookSignatureVerifier;
use PHPUnit\Framework\TestCase;

final class WebhookSignatureVerifierTest extends TestCase
{
    private const SECRET = 'whsecret_test_123';
    private const PAYLOAD = '{"type":"envelope.updated","id":"evt_123"}';

    public function testVerifySucceedsWhenSignatureIsValid(): void
    {
        $this->expectNotToPerformAssertions();

        $timestamp = time();
        $header = $this->buildSignatureHeader($timestamp, array($this->computeSignature($timestamp, self::PAYLOAD)));

        WebhookSignatureVerifier::verify(self::SECRET, $header, self::PAYLOAD);
    }

    public function testVerifySucceedsWhenAnyV1SignatureMatches(): void
    {
        $this->expectNotToPerformAssertions();

        $timestamp = time();
        $header = $this->buildSignatureHeader(
            $timestamp,
            array(
                'deadbeef',
                $this->computeSignature($timestamp, self::PAYLOAD),
                'badc0ffee',
            )
        );

        WebhookSignatureVerifier::verify(self::SECRET, $header, self::PAYLOAD);
    }

    public function testVerifyFailsWhenHeaderMissingValidSignature(): void
    {
        $this->expectException(SignatureVerificationException::class);
        $this->expectExceptionMessage('Missing signature.');

        WebhookSignatureVerifier::verify(self::SECRET, 't=' . time(), self::PAYLOAD);
    }

    public function testVerifyFailsWhenHeaderMissingTimestamp(): void
    {
        $this->expectException(SignatureVerificationException::class);
        $this->expectExceptionMessage('Missing timestamp.');

        WebhookSignatureVerifier::verify(self::SECRET, 'v1=' . $this->computeSignature(time(), self::PAYLOAD), self::PAYLOAD);
    }

    public function testVerifyFailsWhenSignatureIsExpired(): void
    {
        $this->expectException(SignatureVerificationException::class);
        $this->expectExceptionMessage('Signature expired.');

        $timestamp = time() - 301;
        WebhookSignatureVerifier::verify(
            self::SECRET,
            $this->buildSignatureHeader($timestamp, array($this->computeSignature($timestamp, self::PAYLOAD))),
            self::PAYLOAD
        );
    }

    public function testVerifyFailsWhenSignatureIsInvalid(): void
    {
        $this->expectException(SignatureVerificationException::class);
        $this->expectExceptionMessage('Invalid signature.');

        WebhookSignatureVerifier::verify(self::SECRET, $this->buildSignatureHeader(time(), array(str_repeat('a', 64))), self::PAYLOAD);
    }

    /**
     * @param list<string> $signatures
     */
    private function buildSignatureHeader(int $timestamp, array $signatures): string
    {
        $parts = array('t=' . $timestamp);

        foreach ($signatures as $signature) {
            $parts[] = 'v1=' . $signature;
        }

        return implode(',', $parts);
    }

    private function computeSignature(int $timestamp, string $payload): string
    {
        return hash_hmac('sha256', $timestamp . '.' . $payload, self::SECRET);
    }
}
