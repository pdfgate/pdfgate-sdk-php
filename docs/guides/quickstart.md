# Quickstart

## Requirements

- PHP `7.4+`
- `ext-curl`
- `ext-json`

## Install

```bash
composer require pdfgate/pdfgate-sdk-php
```

## Initialize the client

```php
<?php

use PdfGate\PdfGateClient;

$client = new PdfGateClient('live_your_api_key');
```

The SDK selects the base URL from the API key prefix:

- `live_` => `https://api.pdfgate.com`
- `test_` => `https://api-sandbox.pdfgate.com`

## First end-to-end flow

```php
<?php

use PdfGate\PdfGateClient;

$client = new PdfGateClient('live_your_api_key');

$generated = $client->generatePdf([
    'url' => 'https://example.com',
    'pageSizeType' => 'a4',
]);

$document = $client->getDocument($generated->getId(), [
    'preSignedUrlExpiresIn' => 1200,
]);

echo $document->getId();
```

For method-by-method examples, use [API](api.md).
