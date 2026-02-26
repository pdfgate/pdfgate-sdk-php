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

        $headers = $request->headers;
        $headerList = array();

        if ($request->jsonBody !== null) {
            $json = json_encode($request->jsonBody);

            if ($json === false) {
                if (PHP_VERSION_ID < 80000) {
                    $this->curlClient->close($ch);
                }

                throw new RuntimeException('Failed to encode request JSON body.');
            }

            $headers['Content-Type'] = 'application/json';
            if ($this->curlClient->setOpt($ch, CURLOPT_POSTFIELDS, $json) === false) {
                if (PHP_VERSION_ID < 80000) {
                    $this->curlClient->close($ch);
                }

                throw new RuntimeException('Failed to set cURL option CURLOPT_POSTFIELDS.');
            }
        }

        foreach ($headers as $name => $value) {
            $headerList[] = $name . ': ' . $value;
        }

        if ($this->curlClient->setOptArray($ch, array(
            CURLOPT_URL => $request->url,
            CURLOPT_CUSTOMREQUEST => $request->method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headerList,
        )) === false) {
            if (PHP_VERSION_ID < 80000) {
                $this->curlClient->close($ch);
            }

            throw new RuntimeException('Failed to set cURL request options.');
        }

        $responseBody = $this->curlClient->exec($ch);

        # Parse response
        if ($responseBody === false) {
            $error = $this->curlClient->error($ch);

            if (PHP_VERSION_ID < 80000) {
                $this->curlClient->close($ch);
            }

            throw new RuntimeException('cURL request failed: ' . $error);
        }

        $statusCode = (int) $this->curlClient->getInfo($ch, CURLINFO_RESPONSE_CODE);

        if (PHP_VERSION_ID < 80000) {
            $this->curlClient->close($ch);
        }

        return new HttpResponse($statusCode, (string) $responseBody);
    }

}
