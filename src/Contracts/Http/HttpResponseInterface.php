<?php

namespace Rake\Contracts\Http;

/**
 * Standard interface for HTTP responses (compatible with Symfony, Guzzle, ...)
 */
interface HttpResponseInterface
{
    /**
     * Get the response status code
     * @return int
     */
    public function getStatusCode(): int;

    /**
     * Get the response body as string
     * @return string
     */
    public function getBody(): string;

    /**
     * Get all response headers
     * @return array
     */
    public function getHeaders(): array;
}