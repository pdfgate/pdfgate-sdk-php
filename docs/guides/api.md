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

`uploadFile()` always sends `jsonResponse=true`. If both `file` and `url` are passed, the SDK prioritizes `file` and sends multipart form data.

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
