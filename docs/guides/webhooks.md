# Webhook Verification

Use `WebhookSignatureVerifier` with the raw request body and the `x-pdfgate-signature` header value.

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
