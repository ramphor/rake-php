<?php

namespace Rake\DataSource;

/**
 * CSV Data Source
 * Parses CSV data from file or string
 */
class CsvDataSource extends AbstractDataSource
{
    /**
     * Fetch data from CSV
     * 
     * @return array
     */
    public function fetch(): array
    {
        $csvContent = $this->getConfigValue('content');
        $delimiter = $this->getConfigValue('delimiter', ',');
        $hasHeader = $this->getConfigValue('hasHeader', true);
        
        if (empty($csvContent)) {
            throw new \RuntimeException('CSV content is required');
        }

        $this->log('Parsing CSV data');

        try {
            $lines = str_getcsv($csvContent, "\n");
            $data = [];
            $headers = [];

            foreach ($lines as $index => $line) {
                $row = str_getcsv($line, $delimiter);
                
                if ($index === 0 && $hasHeader) {
                    $headers = $row;
                    continue;
                }

                if ($hasHeader && !empty($headers)) {
                    $data[] = array_combine($headers, $row);
                } else {
                    $data[] = $row;
                }
            }

            $this->log('CSV parsed', ['rows' => count($data)]);
            
            return $data;

        } catch (\Exception $e) {
            $this->logError('CSV parse failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Get source type
     */
    public function getType(): string
    {
        return 'csv';
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
                'label' => 'CSV Content',
                'description' => 'Paste CSV data or file content',
                'required' => true,
                'default' => '',
            ],
            [
                'name' => 'delimiter',
                'type' => 'text',
                'label' => 'Delimiter',
                'description' => 'CSV field delimiter',
                'required' => false,
                'default' => ',',
            ],
            [
                'name' => 'hasHeader',
                'type' => 'checkbox',
                'label' => 'Has Header Row',
                'description' => 'First row contains column names',
                'required' => false,
                'default' => true,
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
            throw new \RuntimeException('CSV content is required');
        }

        return true;
    }
}

