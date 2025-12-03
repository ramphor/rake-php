<?php

namespace Rake\Facade;

use Rake\Contracts\Http\HttpClientInterface;
use Rake\Manager\HttpClientManager;

/**
 * Request Facade for Rake Framework
 *
 * Provides static access to HTTP client functionality
 *
 * Available methods (from HttpClientInterface):
 * - static get(string $url, array $options = []): HttpResponseInterface
 * - static post(string $url, array $options = []): HttpResponseInterface
 * - static request(string $method, string $url, array $options = []): HttpResponseInterface
 *
 * Usage Examples:
 * ```php
 * use Rake\Facade\Request;
 *
 * // GET request
 * $response = Request::get('https://api.example.com/users');
 * $data = $response->json();
 *
 * // POST request
 * $response = Request::post('https://api.example.com/users', [
 *     'json' => ['name' => 'John', 'email' => 'john@example.com']
 * ]);
 *
 * // Custom request
 * $response = Request::request('PUT', 'https://api.example.com/users/1', [
 *     'headers' => ['Authorization' => 'Bearer token'],
 *     'json' => ['name' => 'Jane']
 * ]);
 *
 * // With options
 * $response = Request::get('https://example.com', [
 *     'headers' => ['User-Agent' => 'MyApp/1.0'],
 *     'timeout' => 30,
 *     'verify' => false, // SSL verification
 * ]);
 * ```
 */
class Request extends Facade
{
    /**
     * Get the registered name of the component
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return HttpClientManager::class;
    }

    /**
     * Get HTTP client instance
     * Returns HttpClientInterface implementation
     *
     * @return HttpClientInterface
     */
    public static function client(): HttpClientInterface
    {
        return static::getFacadeRoot()->getClient();
    }

    /**
     * Make GET request
     *
     * @param string $url
     * @param array $options
     * @return \Rake\Contracts\Http\HttpResponseInterface
     */
    public static function get(string $url, array $options = [])
    {
        return static::client()->get($url, $options);
    }

    /**
     * Make POST request
     *
     * @param string $url
     * @param array $options
     * @return \Rake\Contracts\Http\HttpResponseInterface
     */
    public static function post(string $url, array $options = [])
    {
        return static::client()->post($url, $options);
    }

    /**
     * Make generic HTTP request
     *
     * @param string $method
     * @param string $url
     * @param array $options
     * @return \Rake\Contracts\Http\HttpResponseInterface
     */
    public static function request(string $method, string $url, array $options = [])
    {
        return static::client()->request($method, $url, $options);
    }
}

