# PDFGate SDK for PHP

Official PHP SDK for the PDFGate HTTP API.

[![CI](https://github.com/pdfgate/pdfgate-sdk-php/actions/workflows/ci.yml/badge.svg)](https://github.com/pdfgate/pdfgate-sdk-php/actions/workflows/ci.yml)
[![Release](https://github.com/pdfgate/pdfgate-sdk-php/actions/workflows/release.yml/badge.svg)](https://github.com/pdfgate/pdfgate-sdk-php/actions/workflows/release.yml)

PDFGate lets you generate, process, and secure PDFs via a simple API:

- HTML or URL to PDF
- Fillable forms and adding form fields
- Create signing envelopes from source documents
- Flatten (all or specific fields), compress, watermark, protect PDFs
- Extract PDF form data
- Delete stored documents
- Manage and verify webhooks

🚀 SDK Documentation: https://pdfgate.github.io/pdfgate-sdk-php<br>
🧭 API Reference: https://pdfgate.github.io/pdfgate-sdk-php/api/<br>
📘 API Documentation: https://pdfgate.com/documentation<br>
🔑 Dashboard & API keys: https://dashboard.pdfgate.com


## Requirements

- PHP `7.4+`
- `ext-curl`
- `ext-json`

## Installation

```bash
composer require pdfgate/pdfgate-sdk-php
```

## Quick Start

```php
<?php

use PdfGate\PdfGateClient;

$client = new PdfGateClient('live_your_api_key');

$generated = $client->generatePdf([
    'url' => 'https://example.com',
    'pageSizeType' => 'a4',
    'preSignedUrlExpiresIn' => 1200
]);

echo $generated->getFileUrl();
```

## Usage Examples

### Generate PDF

```php
$client->generatePdf([
    'html' => '<h1>Hello</h1>',
    'pageSizeType' => 'a4',
    'metadata' => ['source' => 'sdk'],
]);
```

### Upload PDF

```php
$client->uploadFile([
    'file' => new \CURLFile('/absolute/path/source.pdf', 'application/pdf', 'source.pdf'),
    'preSignedUrlExpiresIn' => 1200,
]);
```

### Create Envelope

```php
use PdfGate\Enum\EnvelopeStatus;

$envelope = $client->createEnvelope([
    'requesterName' => 'John Doe',
    'documents' => [
        [
            'sourceDocumentId' => '6642381c5c61',
            'name' => 'Employment Agreement',
            'recipients' => [
                [
                    'email' => 'anna@example.com',
                    'name' => 'Anna Smith',
                ],
            ],
        ],
    ],
    'metadata' => ['customerId' => 'cus_123'],
]);

if ($envelope->getStatus() === EnvelopeStatus::CREATED) {
    echo $envelope->getId();
}
```

### Send Envelope

```php
use PdfGate\Enum\EnvelopeStatus;

$sentEnvelope = $client->sendEnvelope('69c0fa44f83ca6a7015f1c8c');

if ($sentEnvelope->getStatus() === EnvelopeStatus::IN_PROGRESS) {
    echo 'Envelope emails have been sent.';
}
```

### Get Envelope

```php
use PdfGate\Enum\EnvelopeStatus;

$envelope = $client->getEnvelope('69c0fa44f83ca6a7015f1c8c');

if ($envelope->getStatus() === EnvelopeStatus::IN_PROGRESS) {
    echo 'Envelope is still awaiting signatures.';
}
```

### Download File

```php
$stream = $client->getFile($documentId);
$output = fopen('output.pdf', 'wb');
stream_copy_to_stream($stream, $output);
fclose($output);
fclose($stream);
```

### Add Form Fields

```php
use PdfGate\Enum\DocumentFieldType;

$doc = $client->addFormFields([
    'documentId' => $documentId,
    // Customize placeholder fields detected in the PDF, keyed by field name.
    'fieldOverrides' => [
        'signature' => ['role' => 'signer', 'optional' => false],
    ],
    // Or place fields at explicit positions on a given page.
    'fields' => [
        [
            'name' => 'signed_on',
            'type' => DocumentFieldType::DATE,
            'page' => 1,
            'x' => 100,
            'y' => 650,
            'width' => 160,
            'height' => 24,
        ],
    ],
]);
```

### Flatten Specific Fields

```php
$flattened = $client->flattenPdf([
    'documentId' => $documentId,
    // Omit fieldNames to flatten the whole document.
    'fieldNames' => ['signature', 'date'],
]);
```

### Delete a Document

```php
$client->deleteDocument($documentId);
```

### Manage Webhooks

```php
use PdfGate\Enum\WebhookEventType;

// The returned secret is shown only once — store it to verify payloads.
$webhook = $client->createWebhook([
    'url' => 'https://example.com/pdfgate-callback',
    'eventTypes' => [
        WebhookEventType::ENVELOPE_COMPLETED,
        WebhookEventType::ENVELOPE_SENT,
    ],
    'description' => 'Production signing events',
]);

$fetched = $client->getWebhook($webhook->getId());
$client->deleteWebhook($webhook->getId());
```

For complete operation examples (`flattenPdf`, `addFormFields`, `compressPdf`, `protectPdf`, `watermarkPdf`, `extractPdfFormData`, `getDocument`, `deleteDocument`, `createEnvelope`, `sendEnvelope`, `getEnvelope`, `createWebhook`, `getWebhook`, `deleteWebhook`), see [API](docs/guides/api.md).

To download generated files, enable **Save files for one month** in PDFGate Dashboard settings.

## Error Handling

Non-2xx responses throw `PdfGate\Exception\ApiException` with:

- `getStatusCode()`
- `getResponseBody()` (truncated)

Transport and parsing failures throw `PdfGate\Exception\TransportException` and preserve original causes.
Webhook verification failures throw `PdfGate\Exception\SignatureVerificationException`.

See [Error handling guide](docs/guides/error-handling.md) for patterns and retry guidance.

## Webhook Verification

Use [WebhookSignatureVerifier](/Users/ferg/repos/pdfgate-sdk-php/src/Webhook/WebhookSignatureVerifier.php) to verify the `x-pdfgate-signature` header against the raw request body and your webhook secret.

```php
use PdfGate\Exception\SignatureVerificationException;
use PdfGate\Webhook\WebhookSignatureVerifier;

$secret = 'whsecret_...';
$signatureHeader = $_SERVER['HTTP_X_PDFGATE_SIGNATURE'] ?? null;
$rawBody = file_get_contents('php://input');

try {
    WebhookSignatureVerifier::verify($secret, $signatureHeader, $rawBody === false ? '' : $rawBody);
    http_response_code(200);
} catch (SignatureVerificationException $e) {
    error_log($e->getMessage());
    http_response_code(400);
}
```

## Development

This section is the source of truth for setup and test commands.

### Local setup

```bash
composer install
```

### Run tests

Unit tests:

```bash
composer run test:unit
```

Acceptance tests (real API calls):

```bash
PDFGATE_API_KEY=your_key composer run test:acceptance
```

### Static analysis

```bash
composer run stan
```

### Build documentation

Generate API docs (requires phpDocumentor in PATH, or `PHPDOC_BIN`):

```bash
composer run docs:api
```

Render the curated guides into the published site layout:

```bash
composer run docs:site
```

Validate markdown links:

```bash
composer run docs:check-links
```

Run both:

```bash
composer run docs:build
```

The combined docs site is generated into `build/docs/site`, with curated guides at the site root and API reference under `build/docs/site/api`. GitHub Pages publishes that combined artifact.

### Generate the changelog manually

If you want to update `CHANGELOG.md` before or after making a release, run the generator
manually. It reads commit subjects since the previous semver tag and updates `CHANGELOG.md`
for the release version you provide.

Generate changelog content for a release version:

```bash
RELEASE_VERSION=1.2.3 php scripts/prepare-release.php
```

Preview the update without writing `CHANGELOG.md`:

```bash
DRY_RUN=1 RELEASE_VERSION=1.2.3 php scripts/prepare-release.php
```

If there are no updates since the previous release, the script generates a fallback `Changed` note instead of failing.
