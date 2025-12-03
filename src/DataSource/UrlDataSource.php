<?php

namespace Rake\DataSource;

use Rake\Facade\Request;

/**
 * URL Data Source
 * Fetches data from URLs using HTTP requests
 */
class UrlDataSource extends AbstractDataSource
{
    /**
     * Fetch data from URL
     * 
     * @return array Array of raw data items
     */
    public function fetch(): array
    {
        $url = $this->getConfigValue('url');

        if (empty($url)) {
            throw new \RuntimeException('URL is required for UrlDataSource');
        }

        $this->log('Fetching from URL', ['url' => $url]);

        try {
            // Fetch using Request facade
            $response = Request::get($url, [
                'timeout' => $this->getConfigValue('timeout', 30),
                'headers' => $this->getConfigValue('headers', []),
            ]);

            if (!$response->isSuccessful()) {
                throw new \RuntimeException(
                    'HTTP request failed with status ' . $response->getStatusCode()
                );
            }

            $body = $response->getBody();

            $this->log('URL fetch successful', [
                'url' => $url,
                'size' => strlen($body),
            ]);

            // Return as single item
            return [
                [
                    'guid' => 'url:' . md5($url),
                    'raw_data' => $body,
                    'url' => $url,
                    'fetched_at' => date('Y-m-d H:i:s'),
                ],
            ];

        } catch (\Exception $e) {
            $this->logError('URL fetch failed', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Get source type
     */
    public function getType(): string
    {
        return 'url';
    }

    /**
     * Validate configuration
     */
    public function validate(): bool
    {
        if (empty($this->config['url'])) {
            throw new \RuntimeException('URL is required');
        }

        if (!filter_var($this->config['url'], FILTER_VALIDATE_URL)) {
            throw new \RuntimeException('Invalid URL format');
        }

        return true;
    }
}

