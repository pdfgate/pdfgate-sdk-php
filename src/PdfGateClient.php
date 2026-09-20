<?php

declare(strict_types=1);

namespace PdfGate;

use PdfGate\Dto\EmbedLinkResponse;
use PdfGate\Dto\PdfGateEnvelope;
use PdfGate\Dto\PdfGateDocumentMetadata;
use PdfGate\Dto\PdfGateRecipient;
use PdfGate\Dto\WebhookResponse;
use PdfGate\Exception\InvalidArgumentException;
use PdfGate\Exception\InvalidConfigurationException;
use PdfGate\Exception\TransportException;
use PdfGate\Http\ApiRequestHandler;
use PdfGate\Http\CurlHttpTransport;
use PdfGate\Http\HttpTransportInterface;

/**
 * PDFGate API client.
 *
 * @phpstan-import-type GeneratePdfRequestPayload from \PdfGate\Type\Types
 * @phpstan-import-type UploadFileRequestPayload from \PdfGate\Type\Types
 * @phpstan-import-type FlattenPdfRequestPayload from \PdfGate\Type\Types
 * @phpstan-import-type CompressPdfRequestPayload from \PdfGate\Type\Types
 * @phpstan-import-type ProtectPdfRequestPayload from \PdfGate\Type\Types
 * @phpstan-import-type WatermarkPdfRequestPayload from \PdfGate\Type\Types
 * @phpstan-import-type ExtractPdfFormDataRequestPayload from \PdfGate\Type\Types
 * @phpstan-import-type GetDocumentQueryPayload from \PdfGate\Type\Types
 * @phpstan-import-type CreateEnvelopeRequestPayload from \PdfGate\Type\Types
 * @phpstan-import-type CreateEmbedLinkRequestPayload from \PdfGate\Type\Types
 * @phpstan-import-type CreateRecipientRequestPayload from \PdfGate\Type\Types
 * @phpstan-import-type UpdateRecipientRequestPayload from \PdfGate\Type\Types
 * @phpstan-import-type AddFormFieldsRequestPayload from \PdfGate\Type\Types
 * @phpstan-import-type CreateWebhookRequestPayload from \PdfGate\Type\Types
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

        $response = $this->requestHandler->postJson('/v1/generate/pdf', $request);

        return PdfGateDocumentMetadata::fromArray($response);
    }

    /**
     * Uploads a raw PDF file or URL source.
     *
     * @param UploadFileRequestPayload $request Upload request payload.
     * @return PdfGateDocumentMetadata
     */
    public function uploadFile(array $request): PdfGateDocumentMetadata
    {
        if (isset($request['file'])) {
            unset($request['url']);
            $response = $this->requestHandler->postMultipart('/upload', $request);

            return PdfGateDocumentMetadata::fromArray($response);
        }

        $response = $this->requestHandler->postJson('/upload', $request);

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

        $response = $this->requestHandler->postJson('/forms/flatten', $request);

        return PdfGateDocumentMetadata::fromArray($response);
    }

    /**
     * Adds interactive form fields to an existing PDF document.
     *
     * @param AddFormFieldsRequestPayload $request Add form fields request payload.
     * @return PdfGateDocumentMetadata
     */
    public function addFormFields(array $request): PdfGateDocumentMetadata
    {
        $request['jsonResponse'] = true;

        $response = $this->requestHandler->postJson('/forms/fields', $request);

        return PdfGateDocumentMetadata::fromArray($response);
    }

    /**
     * Compresses an existing PDF document.
     *
     * @param CompressPdfRequestPayload $request Compress PDF request payload.
     * @return PdfGateDocumentMetadata
     */
    public function compressPdf(array $request): PdfGateDocumentMetadata
    {
        $request['jsonResponse'] = true;

        $response = $this->requestHandler->postJson('/compress/pdf', $request);

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

        $response = $this->requestHandler->postJson('/protect/pdf', $request);

        return PdfGateDocumentMetadata::fromArray($response);
    }

    /**
     * Adds a text or image watermark to an existing PDF document.
     *
     * @param WatermarkPdfRequestPayload $request Watermark PDF request payload.
     * @return PdfGateDocumentMetadata
     */
    public function watermarkPdf(array $request): PdfGateDocumentMetadata
    {
        $request['jsonResponse'] = true;

        $response = $this->requestHandler->postMultipart('/watermark/pdf', $request);

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
        return $this->requestHandler->postJson('/forms/extract-data', $request);
    }

    /**
     * Retrieves metadata and file details for an existing document.
     *
     * @param string $documentId Existing document ID.
     * @param GetDocumentQueryPayload $query Optional get-document query options.
     * @return PdfGateDocumentMetadata
     */
    public function getDocument(string $documentId, array $query = array()): PdfGateDocumentMetadata
    {
        $response = $this->requestHandler->getJson(
            '/document/' . rawurlencode($documentId),
            $query
        );

        return PdfGateDocumentMetadata::fromArray($response);
    }

    /**
     * Permanently deletes a stored document.
     *
     * A document referenced by a draft or in-progress envelope cannot be deleted
     * until those envelopes are completed or expired.
     *
     * @param string $documentId Existing document ID.
     */
    public function deleteDocument(string $documentId): void
    {
        if (trim($documentId) === '') {
            throw new InvalidArgumentException('Document ID cannot be empty.');
        }

        $this->requestHandler->delete('/document/' . rawurlencode($documentId));
    }

    /**
     * Creates a signing envelope from existing source documents.
     *
     * Each recipient is given either as email and name or as the recipientId
     * of a stored recipient. Recipients marked embedded sign inside your own
     * application via createEmbedLink() and receive no emails from PDFGate.
     *
     * signingOrder is the signing order of the recipient, starting from 1.
     * Recipients sign one after another in this order and a recipient is
     * activated once everyone with a lower value has signed. Recipients with
     * the same value can sign in parallel. Provide it for every recipient of a
     * document or for none. Omitted, all recipients can sign immediately.
     *
     * @param CreateEnvelopeRequestPayload $request Create envelope request payload.
     * @return PdfGateEnvelope
     */
    public function createEnvelope(array $request): PdfGateEnvelope
    {
        $response = $this->requestHandler->postJson('/envelope', $request);

        return $this->buildEnvelopeResponse($response);
    }

    /**
     * Sends an existing envelope to all configured recipients.
     *
     * Embedded recipients receive no email; create their signing links with
     * createEmbedLink() after sending. On documents with a signingOrder only
     * the first recipients are emailed; later recipients are activated as
     * earlier ones sign.
     *
     * @param string $envelopeId Existing envelope ID.
     * @return PdfGateEnvelope
     */
    public function sendEnvelope(string $envelopeId): PdfGateEnvelope
    {
        if (trim($envelopeId) === '') {
            throw new InvalidArgumentException('Envelope ID cannot be empty.');
        }

        $response = $this->requestHandler->postJson(
            '/envelope/' . rawurlencode($envelopeId) . '/send',
            array()
        );

        return $this->buildEnvelopeResponse($response);
    }

    /**
     * Retrieves the current state of an existing envelope.
     *
     * @param string $envelopeId Existing envelope ID.
     * @return PdfGateEnvelope
     */
    public function getEnvelope(string $envelopeId): PdfGateEnvelope
    {
        if (trim($envelopeId) === '') {
            throw new InvalidArgumentException('Envelope ID cannot be empty.');
        }

        $response = $this->requestHandler->getJson(
            '/envelope/' . rawurlencode($envelopeId)
        );

        return $this->buildEnvelopeResponse($response);
    }

    /**
     * Voids (cancels) an envelope in created or in_progress status.
     *
     * Recipients who have not signed yet are notified by email and their
     * signing links stop working; documents already signed by all recipients
     * are not affected. The optional reason (max 500 characters) is visible to
     * recipients: it is included in the cancellation email. This action cannot
     * be undone.
     *
     * @param string $envelopeId Existing envelope ID.
     * @param string|null $reason Optional reason, visible to recipients.
     * @return PdfGateEnvelope
     */
    public function voidEnvelope(string $envelopeId, ?string $reason = null): PdfGateEnvelope
    {
        if (trim($envelopeId) === '') {
            throw new InvalidArgumentException('Envelope ID cannot be empty.');
        }

        $body = array();
        if ($reason !== null && trim($reason) !== '') {
            $body['reason'] = $reason;
        }

        $response = $this->requestHandler->postJson(
            '/envelope/' . rawurlencode($envelopeId) . '/void',
            $body
        );

        return $this->buildEnvelopeResponse($response);
    }

    /**
     * Permanently deletes an envelope and the files it produced.
     *
     * The signed documents and audit logs are removed from storage, recipient
     * data is anonymized, and recipients lose access. Source documents are not
     * deleted. Only envelopes in draft, completed, expired, or voided status
     * can be deleted; void an active envelope first. This action cannot be
     * undone.
     *
     * @param string $envelopeId Existing envelope ID.
     */
    public function deleteEnvelope(string $envelopeId): void
    {
        if (trim($envelopeId) === '') {
            throw new InvalidArgumentException('Envelope ID cannot be empty.');
        }

        $this->requestHandler->delete('/envelope/' . rawurlencode($envelopeId));
    }

    /**
     * Creates a short-lived signing link for an embedded recipient.
     *
     * Render the returned URL in an iframe inside your application. The
     * envelope must be in in_progress status and the link expires after 10
     * minutes, so create it when the signer is ready (one link per signing
     * session). When the session ends the iframe redirects to returnUrl with
     * event (signing_complete, voided, expired or not_found), envelopeId,
     * documentId and recipientId appended as query parameters; existing
     * returnUrl query parameters are preserved. On documents with a
     * signingOrder the link can only be created once it is the recipient's
     * turn (the API returns an error before that); the
     * envelope.recipient.activated webhook signals that moment.
     *
     * @param string $envelopeId Existing envelope ID.
     * @param CreateEmbedLinkRequestPayload $request Create embed link request payload.
     * @return EmbedLinkResponse
     */
    public function createEmbedLink(string $envelopeId, array $request): EmbedLinkResponse
    {
        if (trim($envelopeId) === '') {
            throw new InvalidArgumentException('Envelope ID cannot be empty.');
        }

        $response = $this->requestHandler->postJson(
            '/envelope/' . rawurlencode($envelopeId) . '/embed-link',
            $request
        );

        return EmbedLinkResponse::fromArray($response);
    }

    /**
     * Stores a recipient so envelopes can reference them by recipientId.
     *
     * The email is stored lowercased and cannot be changed later. Emails are
     * not unique; every call creates a new recipient, so list existing
     * recipients first when reuse is intended.
     *
     * @param CreateRecipientRequestPayload $request Create recipient request payload.
     * @return PdfGateRecipient
     */
    public function createRecipient(array $request): PdfGateRecipient
    {
        $response = $this->requestHandler->postJson('/recipient', $request);

        return PdfGateRecipient::fromArray($response);
    }

    /**
     * Lists stored recipients with the given email, oldest first.
     *
     * @param string $email Email to look up (case-insensitive).
     * @return list<PdfGateRecipient>
     */
    public function listRecipients(string $email): array
    {
        if (trim($email) === '') {
            throw new InvalidArgumentException('Email cannot be empty.');
        }

        $response = $this->requestHandler->getJson(
            '/recipients',
            array('email' => $email)
        );

        if (!array_key_exists('recipients', $response) || !is_array($response['recipients'])) {
            throw new TransportException('Expected "recipients" to be an array in list recipients response.');
        }

        $recipients = array();
        foreach ($response['recipients'] as $recipientPayload) {
            if (!is_array($recipientPayload)) {
                throw new TransportException('Expected each recipient response to be an object.');
            }

            $recipients[] = PdfGateRecipient::fromArray($recipientPayload);
        }

        return $recipients;
    }

    /**
     * Retrieves a stored recipient by ID.
     *
     * @param string $recipientId Existing recipient ID.
     * @return PdfGateRecipient
     */
    public function getRecipient(string $recipientId): PdfGateRecipient
    {
        if (trim($recipientId) === '') {
            throw new InvalidArgumentException('Recipient ID cannot be empty.');
        }

        $response = $this->requestHandler->getJson(
            '/recipient/' . rawurlencode($recipientId)
        );

        return PdfGateRecipient::fromArray($response);
    }

    /**
     * Updates a stored recipient's name or metadata.
     *
     * The email cannot be changed. Existing envelopes are not affected; they
     * keep the recipient name they were created with.
     *
     * @param string $recipientId Existing recipient ID.
     * @param UpdateRecipientRequestPayload $request Update recipient request payload.
     * @return PdfGateRecipient
     */
    public function updateRecipient(string $recipientId, array $request): PdfGateRecipient
    {
        if (trim($recipientId) === '') {
            throw new InvalidArgumentException('Recipient ID cannot be empty.');
        }

        $response = $this->requestHandler->patchJson(
            '/recipient/' . rawurlencode($recipientId),
            $request
        );

        return PdfGateRecipient::fromArray($response);
    }

    /**
     * Retrieves a generated PDF file as a readable stream resource.
     *
     * @param string $documentId Generated document identifier.
     * @return resource
     */
    public function getFile(string $documentId)
    {
        if (trim($documentId) === '') {
            throw new InvalidArgumentException('Document ID cannot be empty.');
        }

        $body = $this->requestHandler->getBinary('/file/' . rawurlencode($documentId));
        $stream = fopen('php://temp', 'w+b');

        if ($stream === false) {
            throw new TransportException('Failed to initialize in-memory stream for file download.');
        }

        if (fwrite($stream, $body) === false) {
            fclose($stream);
            throw new TransportException('Failed to write downloaded file into stream.');
        }

        rewind($stream);

        return $stream;
    }

    /**
     * Registers a webhook endpoint to receive PDFGate event notifications.
     *
     * The response includes a secret (returned only once, at creation time) used to
     * verify webhook payloads via the webhook signature verifier.
     *
     * @param CreateWebhookRequestPayload $request Create webhook request payload.
     * @return WebhookResponse
     */
    public function createWebhook(array $request): WebhookResponse
    {
        $response = $this->requestHandler->postJson('/webhook', $request);

        return WebhookResponse::fromArray($response);
    }

    /**
     * Retrieves a registered webhook by ID.
     *
     * The secret is not returned by this endpoint (only at creation time).
     *
     * @param string $webhookId Existing webhook ID.
     * @return WebhookResponse
     */
    public function getWebhook(string $webhookId): WebhookResponse
    {
        if (trim($webhookId) === '') {
            throw new InvalidArgumentException('Webhook ID cannot be empty.');
        }

        $response = $this->requestHandler->getJson('/webhook/' . rawurlencode($webhookId));

        return WebhookResponse::fromArray($response);
    }

    /**
     * Deletes a registered webhook.
     *
     * @param string $webhookId Existing webhook ID.
     */
    public function deleteWebhook(string $webhookId): void
    {
        if (trim($webhookId) === '') {
            throw new InvalidArgumentException('Webhook ID cannot be empty.');
        }

        $this->requestHandler->delete('/webhook/' . rawurlencode($webhookId));
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

    /**
     * @param array<string,mixed> $response
     */
    private function buildEnvelopeResponse(array $response): PdfGateEnvelope
    {
        return PdfGateEnvelope::fromArray($response);
    }
}
