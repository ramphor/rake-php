<?php

namespace Rake\Contracts\Http;

/**
 * Standard interface for HTTP Client adapters (compatible with Symfony HttpClient, Guzzle, ...)
 */
interface HttpClientInterface
{
    /**
     * Send a GET request
     * @param string $url
     * @param array $options (optional) - All request config: headers, auth, base_uri, ...
     * @return HttpResponseInterface
     */
    public function get(string $url, array $options = []): HttpResponseInterface;

    /**
     * Send a POST request
     * @param string $url
     * @param array $options (optional)
     * @return HttpResponseInterface
     */
    public function post(string $url, array $options = []): HttpResponseInterface;

    /**
     * Send a generic HTTP request
     * @param string $method (GET, POST, PUT, DELETE, ...)
     * @param string $url
     * @param array $options (optional)
     * @return HttpResponseInterface
     */
    public function request(string $method, string $url, array $options = []): HttpResponseInterface;
}