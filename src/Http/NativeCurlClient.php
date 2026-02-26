<?php

declare(strict_types=1);

namespace PdfGate\Http;

/**
 * Native cURL adapter backed by PHP's curl_* functions.
 */
class NativeCurlClient implements CurlClientInterface
{
    public function init()
    {
        return curl_init();
    }

    public function setOpt($handle, int $option, $value): bool
    {
        return curl_setopt($handle, $option, $value);
    }

    public function setOptArray($handle, array $options): bool
    {
        return curl_setopt_array($handle, $options);
    }

    public function exec($handle)
    {
        $result = curl_exec($handle);

        if ($result === true) {
            return '';
        }

        return $result;
    }

    public function getInfo($handle, int $option)
    {
        return curl_getinfo($handle, $option);
    }

    public function error($handle): string
    {
        return curl_error($handle);
    }

    public function close($handle): void
    {
        curl_close($handle);
    }
}
