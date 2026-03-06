# Error Handling

The SDK raises two main exception types.

## `PdfGate\Exception\ApiException`

Thrown on non-2xx HTTP responses.

Available data:

- `getStatusCode()`
- `getResponseBody()` (response body truncated for safety)

Typical handling:

```php
use PdfGate\Exception\ApiException;

try {
    $client->generatePdf(['url' => 'https://example.com']);
} catch (ApiException $e) {
    error_log('Status: ' . $e->getStatusCode());
    error_log('Body: ' . $e->getResponseBody());
}
```

## `PdfGate\Exception\TransportException`

Thrown when request execution or response parsing fails (network issues, invalid payloads, stream write failures). The original cause is preserved as the previous exception.

```php
use PdfGate\Exception\TransportException;

try {
    $client->generatePdf(['url' => 'https://example.com']);
} catch (TransportException $e) {
    error_log($e->getMessage());
    if ($e->getPrevious() !== null) {
        error_log(get_class($e->getPrevious()) . ': ' . $e->getPrevious()->getMessage());
    }
}
```

## Retry guidance

- Retry transport errors when they are transient (timeouts, temporary DNS/connectivity failures).
- Do not retry validation/authentication errors until request inputs or credentials are corrected.
- Prefer exponential backoff for automated retries.
