<?php

declare(strict_types=1);

namespace PdfGate\Http;

use RuntimeException;

/**
 * cURL transport implementation for SDK HTTP calls.
 */
class CurlHttpTransport implements HttpTransportInterface
{
    /** @var CurlClientInterface */
    private $curlClient;

    public function __construct(?CurlClientInterface $curlClient = null)
    {
        $this->curlClient = $curlClient ?? new NativeCurlClient();
    }

    /**
     * @param HttpRequest $request
     * @return HttpResponse
     */
    public function send(HttpRequest $request): HttpResponse
    {
        $ch = $this->curlClient->init();

        if ($ch === false) {
            throw new RuntimeException('Failed to initialize cURL.');
        }

        try {
            $headers = $request->getHeaders();

            if ($request->getJsonBody() !== null) {
                $json = json_encode($request->getJsonBody());

                if ($json === false) {
                    throw new RuntimeException('Failed to encode request JSON body.');
                }

                $headers['Content-Type'] = 'application/json';
                if ($this->curlClient->setOpt($ch, CURLOPT_POSTFIELDS, $json) === false) {
                    throw new RuntimeException('Failed to set cURL option CURLOPT_POSTFIELDS.');
                }
            }

            if ($request->getMultipartBody() !== null) {
                $multipartBody = $this->normalizeMultipartBody($request->getMultipartBody());
                if ($this->curlClient->setOpt($ch, CURLOPT_POSTFIELDS, $multipartBody) === false) {
                    throw new RuntimeException('Failed to set cURL option CURLOPT_POSTFIELDS.');
                }
            }

            $headerList = array();
            foreach ($headers as $name => $value) {
                $headerList[] = $name . ': ' . $value;
            }

            if (
                $this->curlClient->setOptArray($ch, array(
                    CURLOPT_URL => $request->getUrl(),
                    CURLOPT_CUSTOMREQUEST => $request->getMethod(),
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT => $request->getTimeout(),
                    CURLOPT_HTTPHEADER => $headerList,
                )) === false
            ) {
                throw new RuntimeException('Failed to set cURL request options.');
            }

            $responseBody = $this->curlClient->exec($ch);

            if ($responseBody === false) {
                $error = $this->curlClient->error($ch);
                throw new RuntimeException('cURL request failed: ' . $error);
            }

            $statusCode = (int) $this->curlClient->getInfo($ch, CURLINFO_RESPONSE_CODE);

            return new HttpResponse($statusCode, (string) $responseBody);
        } finally {
            if (PHP_VERSION_ID < 80000) {
                $this->curlClient->close($ch);
            }
        }
    }

    /**
     * @param array<string,mixed> $payload
     * @return array<string,mixed>
     */
    private function normalizeMultipartBody(array $payload): array
    {
        $normalized = array();

        foreach ($payload as $key => $value) {
            $this->appendMultipartField($normalized, (string) $key, $value);
        }

        return $normalized;
    }

    /**
     * @param array<string,mixed> $normalized
     * @param mixed $value
     */
    private function appendMultipartField(array &$normalized, string $key, $value): void
    {
        if ($value instanceof \CURLFile) {
            $normalized[$key] = $value;
            return;
        }

        if (is_bool($value)) {
            $normalized[$key] = $value ? 'true' : 'false';
            return;
        }

        if (is_array($value)) {
            foreach ($value as $childKey => $childValue) {
                $this->appendMultipartField($normalized, $key . '[' . (string) $childKey . ']', $childValue);
            }
            return;
        }

        if (is_object($value)) {
            /** @var array<string,mixed> $objectFields */
            $objectFields = get_object_vars($value);
            foreach ($objectFields as $childKey => $childValue) {
                $this->appendMultipartField($normalized, $key . '[' . (string) $childKey . ']', $childValue);
            }
            return;
        }

        if ($value === null) {
            return;
        }

        $normalized[$key] = $value;
    }
}
