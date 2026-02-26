<?php

declare(strict_types=1);

namespace PdfGate\Http;

/**
 * Adapter contract for cURL operations used by CurlHttpTransport.
 */
interface CurlClientInterface
{
    /**
     * Initializes a new cURL handle.
     *
     * @return mixed cURL handle or false on failure.
     */
    public function init();

    /**
     * Sets a cURL option.
     *
     * @param mixed $handle cURL handle.
     * @param mixed $value Option value.
     */
    public function setOpt($handle, int $option, $value): bool;

    /**
     * Sets multiple cURL options.
     *
     * @param mixed $handle cURL handle.
     * @param array<int,mixed> $options cURL options map.
     */
    public function setOptArray($handle, array $options): bool;

    /**
     * Executes the cURL request.
     *
     * @param mixed $handle cURL handle.
     * @return string|false
     */
    public function exec($handle);

    /**
     * Returns cURL transfer information.
     *
     * @param mixed $handle cURL handle.
     * @return mixed
     */
    public function getInfo($handle, int $option);

    /**
     * Returns the latest cURL error string.
     *
     * @param mixed $handle cURL handle.
     */
    public function error($handle): string;

    /**
     * Closes a cURL handle.
     *
     * @param mixed $handle cURL handle.
     */
    public function close($handle): void;
}
