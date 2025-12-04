<?php

namespace Rake\CompletionAction;

/**
 * Webhook Completion Action
 * 
 * Send HTTP POST request to webhook URL khi hoàn thành crawl/processing.
 */
class WebhookAction extends AbstractCompletionAction
{
    /**
     * Get action type
     *
     * @return string
     */
    public function getType(): string
    {
        return 'webhook';
    }

    /**
     * Get action name
     *
     * @return string
     */
    public function getName(): string
    {
        return 'Webhook';
    }

    /**
     * Get action description
     *
     * @return string
     */
    public function getDescription(): string
    {
        return 'Send HTTP POST request to webhook URL';
    }

    /**
     * Execute webhook action
     *
     * @param array $context Context data
     * @return array
     */
    public function execute(array $context): array
    {
        $url = $this->getConfigValue('url', '');
        
        if (empty($url)) {
            return [
                'success' => false,
                'message' => 'Webhook URL is required',
                'data' => null
            ];
        }

        // Build payload
        $payload = $this->buildPayload($context);

        // Build headers
        $headers = $this->buildHeaders();

        // Send HTTP POST request
        $response = wp_remote_post($url, [
            'timeout' => $this->getConfigValue('timeout', 30),
            'headers' => $headers,
            'body' => json_encode($payload),
            'sslverify' => $this->getConfigValue('ssl_verify', true)
        ]);

        // Check response
        if (is_wp_error($response)) {
            return [
                'success' => false,
                'message' => 'Webhook request failed: ' . $response->get_error_message(),
                'data' => null
            ];
        }

        $responseCode = wp_remote_retrieve_response_code($response);
        $responseBody = wp_remote_retrieve_body($response);

        if ($responseCode >= 200 && $responseCode < 300) {
            return [
                'success' => true,
                'message' => "Webhook request sent successfully to {$url}",
                'data' => [
                    'url' => $url,
                    'response_code' => $responseCode,
                    'response_body' => $responseBody
                ]
            ];
        } else {
            return [
                'success' => false,
                'message' => "Webhook request failed with status {$responseCode}",
                'data' => [
                    'url' => $url,
                    'response_code' => $responseCode,
                    'response_body' => $responseBody
                ]
            ];
        }
    }

    /**
     * Build webhook payload
     *
     * @param array $context
     * @return array
     */
    private function buildPayload(array $context): array
    {
        $includeFullContext = $this->getConfigValue('include_full_context', false);
        
        if ($includeFullContext) {
            return [
                'event' => 'crawl_completed',
                'timestamp' => current_time('mysql'),
                'context' => $context
            ];
        }

        // Default: summary only
        return [
            'event' => 'crawl_completed',
            'timestamp' => current_time('mysql'),
            'project_name' => $context['project_name'] ?? 'Unknown',
            'project_id' => $context['project_id'] ?? null,
            'total_items_processed' => $context['total_items_processed'] ?? 0,
            'success_count' => $context['success_count'] ?? 0,
            'failure_count' => $context['failure_count'] ?? 0,
            'duration_seconds' => $context['duration_seconds'] ?? 0,
            'start_time' => $context['start_time'] ?? null,
            'end_time' => $context['end_time'] ?? null
        ];
    }

    /**
     * Build HTTP headers
     *
     * @return array
     */
    private function buildHeaders(): array
    {
        $headers = [
            'Content-Type' => 'application/json',
            'User-Agent' => 'CrawlFlow/1.0 Webhook'
        ];

        // Custom headers from config
        $customHeaders = $this->getConfigValue('headers', '');
        if (!empty($customHeaders)) {
            $parsed = json_decode($customHeaders, true);
            if (is_array($parsed)) {
                $headers = array_merge($headers, $parsed);
            }
        }

        // Authorization header
        $authToken = $this->getConfigValue('auth_token', '');
        if (!empty($authToken)) {
            $headers['Authorization'] = 'Bearer ' . $authToken;
        }

        return $headers;
    }

    /**
     * Get configuration fields for UI
     *
     * @return array
     */
    public static function getConfigFields(): array
    {
        return [
            [
                'name' => 'url',
                'type' => 'url',
                'label' => 'Webhook URL',
                'description' => 'HTTP endpoint to send POST request',
                'placeholder' => 'https://example.com/webhook',
                'required' => true
            ],
            [
                'name' => 'auth_token',
                'type' => 'password',
                'label' => 'Authorization Token',
                'description' => 'Bearer token for authentication (optional)',
                'placeholder' => 'your_secret_token',
                'default' => '',
                'required' => false
            ],
            [
                'name' => 'include_full_context',
                'type' => 'checkbox',
                'label' => 'Include Full Context',
                'description' => 'Include all context data in payload (may be large)',
                'default' => false,
                'required' => false
            ],
            [
                'name' => 'timeout',
                'type' => 'number',
                'label' => 'Timeout (seconds)',
                'description' => 'Request timeout',
                'default' => 30,
                'required' => false
            ],
            [
                'name' => 'ssl_verify',
                'type' => 'checkbox',
                'label' => 'Verify SSL',
                'description' => 'Verify SSL certificate',
                'default' => true,
                'required' => false
            ],
            [
                'name' => 'headers',
                'type' => 'textarea',
                'label' => 'Custom Headers',
                'description' => 'Additional HTTP headers (JSON format: {"Header-Name": "value"})',
                'placeholder' => '{"X-Custom-Header": "value"}',
                'default' => '',
                'required' => false
            ]
        ];
    }

    /**
     * Validate configuration
     *
     * @param array $config
     * @return array
     */
    public static function validateConfig(array $config): array
    {
        $errors = [];

        // Validate URL
        if (empty($config['url'])) {
            $errors[] = 'Webhook URL is required';
        } elseif (!filter_var($config['url'], FILTER_VALIDATE_URL)) {
            $errors[] = 'Invalid webhook URL format';
        }

        // Validate custom headers JSON
        if (!empty($config['headers'])) {
            $parsed = json_decode($config['headers'], true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $errors[] = 'Invalid JSON format for custom headers';
            }
        }

        // Validate timeout
        if (isset($config['timeout']) && (!is_numeric($config['timeout']) || $config['timeout'] < 1)) {
            $errors[] = 'Timeout must be a positive number';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
}

