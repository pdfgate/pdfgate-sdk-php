# PDFGate SDK for PHP

Official PHP SDK for the PDFGate HTTP API.

PDFGate lets you generate, process, and secure PDFs via a simple API:

- HTML or URL to PDF
- Fillable forms
- Flatten, compress, watermark, protect PDFs
- Extract PDF form data

📘 Documentation: https://pdfgate.com/documentation<br>
🔑 Dashboard & API keys: https://dashboard.pdfgate.com

## Requirements

- PHP `7.4+`
- `ext-curl`
- `ext-json`

## Installation

```bash
composer require pdfgate/pdfgate-sdk-php
```

## Quick start

```php
<?php

use PdfGate\PdfGateClient;

$client = new PdfGateClient('live_your_api_key');

$generated = $client->generatePdf(
    [
        'url' => 'https://example.com',
        'pageSizeType' => 'a4',
    ]
);

$flattened = $client->flattenPdf(
    [
        'documentId' => $generated->getId(),
        'metadata' => ['source' => 'sdk'],
    ]
);

echo $flattened->getId();
```

The SDK selects the base URL from the API key prefix:

- `live_` => `https://api.pdfgate.com`
- `test_` => `https://api-sandbox.pdfgate.com`

## Examples


### Generate PDF

```php
$client = new PdfGateClient('live_your_api_key');

$client->generatePdf(
    [
        'html' => '<h1>Hello</h1>',
        'pageSizeType' => 'a4',
        'margin' => [
            'top' => '10px',
            'bottom' => '10px',
            'left' => '8px',
            'right' => '8px',
        ],
        'clickSelectorChainSetup' => [
            'ignoreFailingChains' => true,
            'chains' => [
                ['selectors' => ['#cookieDialog']],
                ['selectors' => ['.popupClose']],
            ],
        ],
        'printBackground' => true,
        'metadata' => ['source' => 'sdk'],
    ]
);
```

### Flatten PDF

```php
$client->flattenPdf(
    [
        'documentId' => $id,
        'preSignedUrlExpiresIn' => 1200,
        'metadata' => ['source' => 'sdk'],
    ]
);
```

### Extract PDF Form Data

```php
$client->extractPdfFormData(
    [
        'documentId' => $id,
    ]
);
```


## Error handling

Non-2xx responses throw `PdfGate\Exception\ApiException` with:

- `getStatusCode()`
- `getResponseBody()` (truncated)

Transport and parsing failures throw `PdfGate\Exception\TransportException` and preserve original causes.

## Tests

Run unit tests:

```bash
composer test -- --testsuite unit
```

Run acceptance tests (real API calls):

```bash
PDFGATE_API_KEY=your_key composer test -- --testsuite acceptance
```

You can check types with PHPStan:

```bash
composer run stan
```
