<?php

namespace Rake\DataSource;

/**
 * XML Data Source
 * Parses XML data from file or string
 */
class XmlDataSource extends AbstractDataSource
{
    /**
     * Fetch data from XML
     * 
     * @return array
     */
    public function fetch(): array
    {
        $xmlContent = $this->getConfigValue('content');
        
        if (empty($xmlContent)) {
            throw new \RuntimeException('XML content is required');
        }

        $this->log('Parsing XML data');

        try {
            libxml_use_internal_errors(true);
            $xml = simplexml_load_string($xmlContent);
            
            if ($xml === false) {
                $errors = libxml_get_errors();
                $errorMsg = !empty($errors) ? $errors[0]->message : 'Invalid XML';
                throw new \RuntimeException('XML parse error: ' . $errorMsg);
            }

            // Convert to array
            $data = json_decode(json_encode($xml), true);
            
            $this->log('XML parsed');
            
            return $data;

        } catch (\Exception $e) {
            $this->logError('XML parse failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Get source type
     */
    public function getType(): string
    {
        return 'xml';
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
                'name' => 'content',
                'type' => 'textarea',
                'label' => 'XML Content',
                'description' => 'Paste XML data or file content',
                'required' => true,
                'default' => '',
            ],
            [
                'name' => 'scanUrls',
                'type' => 'checkbox',
                'label' => 'Scan for URLs',
                'description' => 'Extract URLs from XML content',
                'required' => false,
                'default' => true,
            ],
            [
                'name' => 'domainPolicy',
                'type' => 'select',
                'label' => 'Domain Policy',
                'description' => 'URL filtering policy',
                'required' => false,
                'default' => 'all',
                'options' => [
                    ['value' => 'all', 'label' => 'All Domains'],
                    ['value' => 'whitelist', 'label' => 'Whitelist Only'],
                ],
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
        $content = $this->getConfigValue('content');
        
        if (empty($content)) {
            throw new \RuntimeException('XML content is required');
        }

        return true;
    }
}

