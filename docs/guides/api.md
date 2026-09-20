# Usage Guide

## Generate PDF

```php
$client->generatePdf([
    'html' => '<h1>Hello</h1>',
    'pageSizeType' => 'a4',
    'printBackground' => true,
    'metadata' => ['source' => 'sdk'],
]);
```

## Upload File

If both `file` and `url` are passed, `uploadFile()` prioritizes `file` and sends multipart form data.

```php
$client->uploadFile([
    'file' => new \CURLFile('/absolute/path/source.pdf', 'application/pdf', 'source.pdf'),
    'preSignedUrlExpiresIn' => 1200,
]);
```

```php
$client->uploadFile([
    'url' => 'https://example.com/source.pdf',
    'preSignedUrlExpiresIn' => 1200,
]);
```

## Create Envelope

`createEnvelope()` sends JSON with nested envelope documents and recipients. Optional fields like `metadata` and recipient `role` are omitted automatically when set to `null`. Each recipient is given either as `email` and `name` or as the `recipientId` of a stored recipient (see [Create Recipient](#create-recipient)). Recipients marked `embedded` receive no email and get their signing links via `createEmbedLink()` after sending. Optional `signingOrder` (given for every recipient of a document or for none) makes recipients sign one after another: each recipient is activated — and emailed their signing link — once everyone with a lower value has signed, and the `envelope.recipient.activated` webhook event fires at that moment.

```php
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
                    'signingOrder' => 1,
                ],
                [
                    'email' => 'bob@example.com',
                    'name' => 'Bob Jones',
                    'signingOrder' => 2,
                ],
            ],
        ],
    ],
    'metadata' => ['customerId' => 'cus_123'],
]);
```

## Send Envelope

`sendEnvelope()` emails each recipient a secure signing link. Links stay valid until the envelope expires (30 days after creation by default, configurable with `expiresInDays` or the account's signing settings) and recipients must complete OTP verification before entering the signing flow. Embedded recipients receive no email; create their signing links with `createEmbedLink()` after sending.

```php
$sentEnvelope = $client->sendEnvelope('69c0fa44f83ca6a7015f1c8c');
```

## Get Envelope

`getEnvelope()` retrieves the current envelope state so you can inspect overall status, document progress, and recipient status details.

```php
$envelope = $client->getEnvelope('69c0fa44f83ca6a7015f1c8c');
```

## Void Envelope

`voidEnvelope()` cancels an envelope in `created` or `in_progress` status. Recipients who have not signed are notified by email and their signing links stop working; documents already signed by all recipients are not affected. The optional reason is visible to recipients.

```php
$voided = $client->voidEnvelope('69c0fa44f83ca6a7015f1c8c', 'Contract terms changed');
```

## Delete Envelope

`deleteEnvelope()` permanently deletes an envelope and the files it produced (signed documents and audit logs). Recipient data is anonymized and recipients lose access; source documents are not deleted. Only envelopes in `draft`, `completed`, `expired`, or `voided` status can be deleted — void an active envelope first.

```php
$client->deleteEnvelope('69c0fa44f83ca6a7015f1c8c');
```

## Create Embed Link

`createEmbedLink()` creates a short-lived signing link for an embedded recipient; render the returned URL in an iframe inside your application. The envelope must be in `in_progress` status and the link expires after 10 minutes, so create it when the signer is ready (one link per signing session). When the session ends the iframe redirects to `returnUrl` with `event` (`signing_complete`, `voided`, `expired` or `not_found`), `envelopeId`, `documentId` and `recipientId` appended as query parameters; existing `returnUrl` query parameters are preserved.

```php
$embedLink = $client->createEmbedLink('69c0fa44f83ca6a7015f1c8c', [
    'documentId' => '6642381c5c61',
    'recipientId' => 'rcp_1a2b3c4d5e6f',
    'returnUrl' => 'https://example.com/signed',
]);

echo $embedLink->getUrl();
```

## Create Recipient

`createRecipient()` stores a recipient so envelopes can reference them by `recipientId`. The email is stored lowercased and cannot be changed later. Emails are not unique; every call creates a new recipient, so list existing recipients first when reuse is intended.

```php
$recipient = $client->createRecipient([
    'email' => 'anna@example.com',
    'name' => 'Anna Smith',
    'metadata' => ['customerId' => 'cus_123'],
]);
```

## List Recipients

`listRecipients()` returns the stored recipients with the given email (case-insensitive), oldest first.

```php
$recipients = $client->listRecipients('anna@example.com');
```

## Get Recipient

`getRecipient()` retrieves a stored recipient by ID.

```php
$recipient = $client->getRecipient('rcp_1a2b3c4d5e6f');
```

## Update Recipient

`updateRecipient()` updates a stored recipient's name or metadata. The email cannot be changed. Existing envelopes are not affected; they keep the recipient name they were created with.

```php
$updated = $client->updateRecipient('rcp_1a2b3c4d5e6f', [
    'name' => 'Anna Jones',
]);
```

## Flatten PDF

```php
$client->flattenPdf([
    'documentId' => $id,
    'preSignedUrlExpiresIn' => 1200,
]);
```

## Compress PDF

```php
$client->compressPdf([
    'documentId' => $id,
    'linearize' => true,
]);
```

## Protect PDF

```php
$client->protectPdf([
    'documentId' => $id,
    'algorithm' => 'AES256',
    'ownerPassword' => 'ownerPassword',
    'userPassword' => 'userPassword',
    'disablePrint' => true,
    'disableCopy' => true,
]);
```

## Watermark PDF

`watermarkPdf()` always sends multipart form data and forces `jsonResponse=true`.

```php
$client->watermarkPdf([
    'documentId' => $id,
    'type' => 'text',
    'text' => 'Confidential',
    'fontColor' => 'rgb(156, 50, 168)',
    'rotate' => 30,
    'opacity' => 0.2,
]);
```

```php
$client->watermarkPdf([
    'documentId' => $id,
    'type' => 'image',
    'watermark' => new \CURLFile('/absolute/path/watermark.png'),
    'imageWidth' => 120,
    'imageHeight' => 120,
]);
```

## Extract PDF Form Data

```php
$formData = $client->extractPdfFormData([
    'documentId' => $id,
]);
```

## Get Document Metadata

```php
$document = $client->getDocument($id, [
    'preSignedUrlExpiresIn' => 1200,
]);
```

## Get File Stream

```php
$stream = $client->getFile($id);
$output = fopen('output.pdf', 'wb');
stream_copy_to_stream($stream, $output);
fclose($output);
fclose($stream);
```

To download generated files, enable **Save files for one month** in PDFGate Dashboard settings.
