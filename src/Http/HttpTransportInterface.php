<?php

declare(strict_types=1);

namespace PdfGate\Http;

/**
 * HTTP transport contract used by the SDK client.
 */
interface HttpTransportInterface
{
    /**
     * Sends an HTTP request and returns the response.
     *
     * @param HttpRequest $request Request to send.
     * @return HttpResponse
     */
    public function send(HttpRequest $request): HttpResponse;
}
