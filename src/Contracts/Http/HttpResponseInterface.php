<?php

namespace Rake\Contracts\Http;

/**
 * HTTP Response Interface
 * Standard interface for HTTP responses
 */
interface HttpResponseInterface
{
    /**
     * Get HTTP status code
     *
     * @return int
     */
    public function getStatusCode(): int;

    /**
     * Get response body
     *
     * @return string
     */
    public function getBody(): string;

    /**
     * Get response headers
     *
     * @return array
     */
    public function getHeaders(): array;

    /**
     * Check if response is successful (2xx status code)
     *
     * @return bool
     */
    public function isSuccessful(): bool;

    /**
     * Get response body as JSON array
     *
     * @return array
     */
    public function json(): array;
}
