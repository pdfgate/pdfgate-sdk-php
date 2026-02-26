<?php

declare(strict_types=1);

namespace PdfGate;

use PdfGate\Dto\PdfGateDocumentMetadata;
use PdfGate\Exception\InvalidConfigurationException;
use PdfGate\Http\ApiRequestHandler;
use PdfGate\Http\CurlHttpTransport;
use PdfGate\Http\HttpTransportInterface;

/**
 * PDFGate API client.
 *
 * @phpstan-import-type GeneratePdfRequestPayload from \PdfGate\Type\Types
 * @phpstan-import-type FlattenPdfRequestPayload from \PdfGate\Type\Types
 * @phpstan-import-type ProtectPdfRequestPayload from \PdfGate\Type\Types
 * @phpstan-import-type ExtractPdfFormDataRequestPayload from \PdfGate\Type\Types
 */
class PdfGateClient
{
    private const PROD_BASE_URL = 'https://api.pdfgate.com';
    private const SANDBOX_BASE_URL = 'https://api-sandbox.pdfgate.com';

    /** @var ApiRequestHandler */
    private $requestHandler;

    /**
     * @param string $apiKey PDFGate API key.
     */
    public function __construct(string $apiKey)
    {
        $this->requestHandler = $this->createRequestHandler($apiKey, new CurlHttpTransport());
    }

    /**
     * @internal Intended only for tests and SDK-internal wiring.
     *
     * @param string $apiKey PDFGate API key.
     * @param HttpTransportInterface $transport Custom HTTP transport.
     */
    public static function createWithTransport(string $apiKey, HttpTransportInterface $transport): self
    {
        $client = new self($apiKey);
        $client->requestHandler = $client->createRequestHandler($apiKey, $transport);

        return $client;
    }

    private function createRequestHandler(string $apiKey, HttpTransportInterface $transport): ApiRequestHandler
    {
        if (trim($apiKey) === '') {
            throw new InvalidConfigurationException('API key cannot be empty.');
        }

        return new ApiRequestHandler(
            $this->resolveBaseUrl($apiKey),
            $apiKey,
            $transport
        );
    }

    /**
     * Generates a PDF from HTML or URL.
     *
     * @param GeneratePdfRequestPayload $request Generate PDF request payload.
     * @return PdfGateDocumentMetadata
     */
    public function generatePdf(array $request): PdfGateDocumentMetadata
    {
        $request['jsonResponse'] = true;

        $response = $this->requestHandler->postJsonResponse('/v1/generate/pdf', $request);

        return PdfGateDocumentMetadata::fromArray($response);
    }

    /**
     * Flattens an existing PDF document.
     *
     * @param FlattenPdfRequestPayload $request Flatten PDF request payload.
     * @return PdfGateDocumentMetadata
     */
    public function flattenPdf(array $request): PdfGateDocumentMetadata
    {
        $request['jsonResponse'] = true;

        $response = $this->requestHandler->postJsonResponse('/forms/flatten', $request);

        return PdfGateDocumentMetadata::fromArray($response);
    }

    /**
     * Protects an existing PDF document with encryption and permission restrictions.
     *
     * @param ProtectPdfRequestPayload $request Protect PDF request payload.
     * @return PdfGateDocumentMetadata
     */
    public function protectPdf(array $request): PdfGateDocumentMetadata
    {
        $request['jsonResponse'] = true;

        $response = $this->requestHandler->postJsonResponse('/protect/pdf', $request);

        return PdfGateDocumentMetadata::fromArray($response);
    }

    /**
     * Extracts PDF form fields and values for an existing document.
     *
     * @param ExtractPdfFormDataRequestPayload $request Extract PDF form data request payload.
     * @return array<string,mixed>
     */
    public function extractPdfFormData(array $request): array
    {
        $request['jsonResponse'] = true;

        return $this->requestHandler->postJsonResponse('/forms/extract-data', $request);
    }

    private function resolveBaseUrl(string $apiKey): string
    {
        if (strpos($apiKey, 'live_') === 0) {
            return self::PROD_BASE_URL;
        }

        if (strpos($apiKey, 'test_') === 0) {
            return self::SANDBOX_BASE_URL;
        }

        throw new InvalidConfigurationException('API key must start with "live_" or "test_".');
    }
}
