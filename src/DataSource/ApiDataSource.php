<?php

namespace Rake\DataSource;

use Rake\Facade\Request;

/**
 * API Data Source
 * Fetches data from REST API endpoints
 */
class ApiDataSource extends AbstractDataSource
{
    /**
     * Fetch data from API
     * image.png
     * @return array
     */
    public function fetch(): array
    {
        $url = $this->getConfigValue('url');
        
        if (empty($url)) {
            throw new \RuntimeException('API URL is required');
        }

        $this->log('Fetching from API', ['url' => $url]);

        try {
            $response = Request::get($url, [
                'timeout' => $this->getConfigValue('timeout', 30),
                'headers' => $this->getConfigValue('headers', []),
            ]);

            if (!$response->isSuccessful()) {
                throw new \RuntimeException('API request failed with status ' . $response->getStatusCode());
            }

            $data = $response->json();
            
            $this->log('API data fetched', ['items' => count($data)]);
            
            return $data;

        } catch (\Exception $e) {
            $this->logError('API fetch failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Get source type
     */
    public function getType(): string
    {
        return 'api';
    }

    /**
     * Get configuration fields for UI
     * 
     * @return array
     */
    public static function getDataSourceConfigFields(): array
    {
        return [
            [
                'name' => 'url',
                'type' => 'url',
                'label' => 'API URL',
                'description' => 'The API endpoint URL to fetch data from',
                'required' => true,
                'default' => 'https://api.example.com/data',
            ],
            [
                'name' => 'timeout',
                'type' => 'number',
                'label' => 'Timeout (seconds)',
                'description' => 'Request timeout in seconds',
                'required' => false,
                'default' => 30,
            ],
            [
                'name' => 'headers',
                'type' => 'textarea',
                'label' => 'Custom Headers',
                'description' => 'Additional HTTP headers (JSON format)',
                'required' => false,
                'default' => '{}',
            ],
        ];
    }

    /**
     * Validate configuration
     * 
     * @return bool
     */
    public function validate(): bool
    {
        $url = $this->getConfigValue('url');
        
        if (empty($url)) {
            throw new \RuntimeException('API URL is required');
        }

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new \RuntimeException('Invalid URL format');
        }

        return true;
    }
}

