<?php

namespace Rake\Processor;

use Rake\Contracts\Entities\ParsedDataItemInterface;
use Rake\Facade\Request;

/**
 * Send to API Processor
 * Sends extracted data to external API endpoint
 */
class SendToApiProcessor extends AbstractProcessor
{
    /**
     * Process and send data to API
     * 
     * @param ParsedDataItemInterface $item Extracted data item
     * @return ParsedDataItemInterface
     */
    public function process(ParsedDataItemInterface $item): ParsedDataItemInterface
    {
        // Skip if already null item
        if ($item->isNull()) {
            return $item;
        }

        // Get configuration
        $endpointUrl = $this->getConfig('endpointUrl');
        $method = $this->getConfig('method', 'POST');
        $authType = $this->getConfig('authType', 'none');
        $authDetails = $this->getConfig('authDetails', []);
        $customHeaders = $this->getConfig('headers', []);

        // Validate required config
        if (empty($endpointUrl)) {
            return $this->createNullItem('API endpoint URL is required');
        }

        $this->log('Sending to API', [
            'endpoint' => $endpointUrl,
            'method' => $method,
        ]);

        try {
            // Prepare headers
            $headers = $this->buildHeaders($authType, $authDetails, $customHeaders);
            
            // Get data
            $data = $item->getData();
            
            // Apply field mappings if configured
            $data = $this->applyFieldMappings($data);
            
            // Send request
            $response = Request::request($method, $endpointUrl, [
                'headers' => $headers,
                'json' => $data,
            ]);

            if ($response->isSuccessful()) {
                $this->log('API request successful', [
                    'status_code' => $response->getStatusCode(),
                ]);
                
                return $item
                    ->set('api_sent', true)
                    ->set('api_response_code', $response->getStatusCode());
            } else {
                $this->logError('API request failed', [
                    'status_code' => $response->getStatusCode(),
                    'body' => $response->getBody(),
                ]);
                
                return $this->createNullItem(
                    'API request failed with status ' . $response->getStatusCode()
                );
            }

        } catch (\Exception $e) {
            $this->logError('API error', ['error' => $e->getMessage()]);
            return $this->createNullItem('API error: ' . $e->getMessage());
        }
    }

    /**
     * Build headers with authentication
     */
    private function buildHeaders(string $authType, array $authDetails, array $customHeaders): array
    {
        $headers = ['Content-Type' => 'application/json'];

        // Add authentication
        if ($authType === 'api-key' && isset($authDetails['location'])) {
            if ($authDetails['location'] === 'header') {
                $keyName = $authDetails['keyName'] ?? 'X-API-Key';
                $keyValue = $authDetails['keyValue'] ?? '';
                $headers[$keyName] = $keyValue;
            }
        } elseif ($authType === 'bearer' && isset($authDetails['token'])) {
            $headers['Authorization'] = 'Bearer ' . $authDetails['token'];
        } elseif ($authType === 'basic' && isset($authDetails['username'], $authDetails['password'])) {
            $credentials = base64_encode($authDetails['username'] . ':' . $authDetails['password']);
            $headers['Authorization'] = 'Basic ' . $credentials;
        }

        // Add custom headers
        foreach ($customHeaders as $header) {
            if (isset($header['key'], $header['value'])) {
                $headers[$header['key']] = $header['value'];
            }
        }

        return $headers;
    }

    /**
     * Apply field mappings to data
     * 
     * @param array $data Original data
     * @return array Mapped data
     */
    private function applyFieldMappings(array $data): array
    {
        $autoMapFields = $this->getConfig('autoMapFields', false);
        $fieldMappings = $this->getConfig('fieldMappings', []);

        // If auto-map enabled or no mappings, return as-is
        if ($autoMapFields || empty($fieldMappings)) {
            return $data;
        }

        // Apply custom mappings
        $mappedData = [];
        foreach ($fieldMappings as $extractedField => $targetField) {
            if (isset($data[$extractedField])) {
                $mappedData[$targetField] = $data[$extractedField];
            }
        }

        return $mappedData;
    }
}

