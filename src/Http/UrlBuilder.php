<?php

declare(strict_types=1);

namespace PdfGate\Http;

use InvalidArgumentException;

/**
 * Builds request URLs from domain, path, and optional query parameters.
 */
class UrlBuilder
{
    /** @var string */
    private $domain = '';

    /** @var string */
    private $path = '/';

    /** @var array<string,mixed> */
    private $query = array();

    /**
     * Sets the base domain (scheme + host) for the URL.
     */
    public function withDomain(string $domain): self
    {
        $this->domain = $domain;
        return $this;
    }

    /**
     * Sets the request path and validates it during build.
     */
    public function withPath(string $path): self
    {
        $this->path = $path;
        return $this;
    }

    /**
     * Sets query parameters to append to the URL.
     *
     * @param array<string,mixed> $query
     */
    public function withQuery(array $query): self
    {
        $this->query = $query;
        return $this;
    }

    /**
     * Builds the final URL string from domain, path, and query values.
     */
    public function build(): string
    {
        if ($this->path === '' || $this->path[0] !== '/') {
            throw new InvalidArgumentException('Request path must start with "/".');
        }

        if (preg_match('/[\r\n]/', $this->path) === 1) {
            throw new InvalidArgumentException('Request path contains invalid characters.');
        }

        $fullUrl = rtrim($this->domain, '/') . $this->path;

        if (!empty($this->query)) {
            $query = http_build_query($this->query, '', '&', PHP_QUERY_RFC3986);
            $joinChar = (strpos($fullUrl, '?') === false ? '?' : '&');
            $fullUrl .= $joinChar . $query;
        }

        return $fullUrl;
    }
}
