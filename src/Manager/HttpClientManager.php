<?php

namespace Rake\Manager;

use Rake\Contracts\Http\HttpClientInterface;

/**
 * HTTP Client Manager
 * Manages HTTP client instances and provides default client
 */
class HttpClientManager
{
    /**
     * @var HttpClientInterface|null Default HTTP client
     */
    private static ?HttpClientInterface $defaultClient = null;

    /**
     * @var array Registered HTTP clients
     */
    private static array $clients = [];

    /**
     * @var string Default client name
     */
    private static string $defaultClientName = 'default';

    /**
     * Register an HTTP client
     *
     * @param string $name Client name
     * @param HttpClientInterface $client Client instance
     * @return void
     */
    public static function register(string $name, HttpClientInterface $client): void
    {
        self::$clients[$name] = $client;
    }

    /**
     * Set default HTTP client
     *
     * @param HttpClientInterface $client Client instance
     * @return void
     */
    public static function setDefaultClient(HttpClientInterface $client): void
    {
        self::$defaultClient = $client;
    }

    /**
     * Set default client by name
     *
     * @param string $name Registered client name
     * @return void
     * @throws \RuntimeException If client not found
     */
    public static function setDefaultClientName(string $name): void
    {
        if (!isset(self::$clients[$name])) {
            throw new \RuntimeException("HTTP client '{$name}' not registered");
        }

        self::$defaultClientName = $name;
        self::$defaultClient = self::$clients[$name];
    }

    /**
     * Get HTTP client by name
     *
     * @param string|null $name Client name (null = default)
     * @return HttpClientInterface
     * @throws \RuntimeException If no client available
     */
    public function getClient(?string $name = null): HttpClientInterface
    {
        // If specific name requested
        if ($name !== null) {
            if (isset(self::$clients[$name])) {
                return self::$clients[$name];
            }
            throw new \RuntimeException("HTTP client '{$name}' not found");
        }

        // Return default client
        if (self::$defaultClient !== null) {
            return self::$defaultClient;
        }

        // Try to get default client by name
        if (isset(self::$clients[self::$defaultClientName])) {
            self::$defaultClient = self::$clients[self::$defaultClientName];
            return self::$defaultClient;
        }

        // Create WordPress default client if available
        if (function_exists('wp_remote_get')) {
            $defaultClient = $this->createWordPressClient();
            self::setDefaultClient($defaultClient);
            return $defaultClient;
        }

        throw new \RuntimeException('No HTTP client available. Register a client using HttpClientManager::register()');
    }

    /**
     * Get default HTTP client
     *
     * @return HttpClientInterface
     */
    public static function getDefaultClient(): HttpClientInterface
    {
        $manager = new self();
        return $manager->getClient();
    }

    /**
     * Check if client is registered
     *
     * @param string $name Client name
     * @return bool
     */
    public static function has(string $name): bool
    {
        return isset(self::$clients[$name]);
    }

    /**
     * Get all registered clients
     *
     * @return array
     */
    public static function getClients(): array
    {
        return self::$clients;
    }

    /**
     * Create WordPress-based HTTP client
     * Uses wp_remote_get/wp_remote_post functions
     *
     * @return HttpClientInterface
     */
    private function createWordPressClient(): HttpClientInterface
    {
        return new class implements HttpClientInterface {
            public function get(string $url, array $options = []): \Rake\Contracts\Http\HttpResponseInterface
            {
                return $this->request('GET', $url, $options);
            }

            public function post(string $url, array $options = []): \Rake\Contracts\Http\HttpResponseInterface
            {
                return $this->request('POST', $url, $options);
            }

            public function request(string $method, string $url, array $options = []): \Rake\Contracts\Http\HttpResponseInterface
            {
                $args = [
                    'method' => strtoupper($method),
                    'timeout' => $options['timeout'] ?? 30,
                    'headers' => $options['headers'] ?? [],
                    'user-agent' => $options['user_agent'] ?? 'Rake/2.0',
                    'sslverify' => $options['verify'] ?? false,
                ];

                // Add body for POST/PUT
                if (isset($options['json'])) {
                    $args['body'] = json_encode($options['json']);
                    $args['headers']['Content-Type'] = 'application/json';
                } elseif (isset($options['body'])) {
                    $args['body'] = $options['body'];
                }

                // Make request
                if ($method === 'GET') {
                    $response = wp_remote_get($url, $args);
                } else {
                    $response = wp_remote_request($url, $args);
                }

                // Handle WP_Error
                if (is_wp_error($response)) {
                    throw new \RuntimeException('HTTP request failed: ' . $response->get_error_message());
                }

                // Return response wrapper
                return new class($response) implements \Rake\Contracts\Http\HttpResponseInterface {
                    private $wpResponse;

                    public function __construct($wpResponse)
                    {
                        $this->wpResponse = $wpResponse;
                    }

                    public function getStatusCode(): int
                    {
                        return wp_remote_retrieve_response_code($this->wpResponse);
                    }

                    public function getBody(): string
                    {
                        return wp_remote_retrieve_body($this->wpResponse);
                    }

                    public function getHeaders(): array
                    {
                        return wp_remote_retrieve_headers($this->wpResponse)->getAll();
                    }

                    public function isSuccessful(): bool
                    {
                        $code = $this->getStatusCode();
                        return $code >= 200 && $code < 300;
                    }

                    public function json(): array
                    {
                        $decoded = json_decode($this->getBody(), true);
                        return $decoded ?? [];
                    }
                };
            }
        };
    }

    /**
     * Clear all registered clients (for testing)
     *
     * @return void
     */
    public static function clearAll(): void
    {
        self::$clients = [];
        self::$defaultClient = null;
        self::$defaultClientName = 'default';
    }
}
