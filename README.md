# PDFGate SDK for PHP

Official PHP SDK for the PDFGate HTTP API.

[![CI](https://github.com/pdfgate/pdfgate-sdk-php/actions/workflows/ci.yml/badge.svg)](https://github.com/pdfgate/pdfgate-sdk-php/actions/workflows/ci.yml)
[![Release](https://github.com/pdfgate/pdfgate-sdk-php/actions/workflows/release.yml/badge.svg)](https://github.com/pdfgate/pdfgate-sdk-php/actions/workflows/release.yml)

PDFGate lets you generate, process, and secure PDFs via a simple API:

- HTML or URL to PDF
- Fillable forms
- Flatten, compress, watermark, protect PDFs
- Extract PDF form data

🚀 SDK Documentation: https://pdfgate.github.io/pdfgate-sdk-php<br>
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

### Download File

```php
$stream = $client->getFile($documentId);
$output = fopen('output.pdf', 'wb');
stream_copy_to_stream($stream, $output);
fclose($output);
fclose($stream);
```

For complete operation examples (`flattenPdf`, `compressPdf`, `protectPdf`, `watermarkPdf`, `extractPdfFormData`, `getDocument`), see [API](docs/guides/api.md).

To download generated files, enable **Save files for one month** in PDFGate Dashboard settings.

## Error Handling

Non-2xx responses throw `PdfGate\Exception\ApiException` with:

- `getStatusCode()`
- `getResponseBody()` (truncated)

Transport and parsing failures throw `PdfGate\Exception\TransportException` and preserve original causes.

See [Error handling guide](docs/guides/error-handling.md) for patterns and retry guidance.

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

Validate markdown links:

```bash
composer run docs:check-links
```

Run both:

```bash
composer run docs:build
```

API docs are generated into `build/docs/api` and published to GitHub Pages by CI.
