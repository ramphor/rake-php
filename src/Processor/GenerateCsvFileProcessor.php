<?php

namespace Rake\Processor;

use Rake\Contracts\Entities\ParsedDataItemInterface;

/**
 * Generate CSV File Processor
 * Generates CSV file from extracted data
 */
class GenerateCsvFileProcessor extends AbstractProcessor
{
    private $fileHandle = null;
    private $filePath = null;
    private $headerWritten = false;

    /**
     * Process and write data to CSV
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
        $fileName = $this->getConfig('fileName', 'crawl_results_{{date}}.csv');
        $delimiter = $this->getConfig('delimiter', ',');
        $includeHeader = $this->getConfig('includeHeader', true);

        // Replace placeholders in filename
        $fileName = $this->replaceFilePlaceholders($fileName);

        // Get or create file handle
        if ($this->fileHandle === null) {
            $this->filePath = $this->getOutputPath($fileName);
            $this->fileHandle = fopen($this->filePath, 'a');
            
            if (!$this->fileHandle) {
                return $this->createNullItem('Failed to open CSV file for writing');
            }
        }

        try {
            // Get data
            $data = $item->getData();
            
            // Apply field mappings if configured
            $data = $this->applyFieldMappings($data);
            
            // Write header if needed
            if ($includeHeader && !$this->headerWritten && filesize($this->filePath) === 0) {
                fputcsv($this->fileHandle, array_keys($data), $delimiter);
                $this->headerWritten = true;
            }

            // Write data row
            fputcsv($this->fileHandle, array_values($data), $delimiter);
            
            $this->log('Data written to CSV', ['file' => $this->filePath]);
            
            return $item->set('csv_written', true)->set('csv_file', $this->filePath);

        } catch (\Exception $e) {
            $this->logError('CSV write error', ['error' => $e->getMessage()]);
            return $this->createNullItem('CSV error: ' . $e->getMessage());
        }
    }

    /**
     * Replace placeholders in filename
     */
    private function replaceFilePlaceholders(string $fileName): string
    {
        $replacements = [
            '{{date}}' => date('Y-m-d'),
            '{{datetime}}' => date('Y-m-d_H-i-s'),
            '{{timestamp}}' => time(),
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $fileName);
    }

    /**
     * Get output path for CSV file
     */
    private function getOutputPath(string $fileName): string
    {
        // Default to wp-content/uploads/rake-csv/
        if (function_exists('wp_upload_dir')) {
            $uploadDir = wp_upload_dir();
            $baseDir = $uploadDir['basedir'] . '/rake-csv';
        } else {
            $baseDir = sys_get_temp_dir() . '/rake-csv';
        }

        // Create directory if not exists
        if (!is_dir($baseDir)) {
            mkdir($baseDir, 0755, true);
        }

        return $baseDir . '/' . $fileName;
    }

    /**
     * Destructor - close file handle
     */
    public function __destruct()
    {
        if ($this->fileHandle !== null) {
            fclose($this->fileHandle);
            $this->log('CSV file closed', ['file' => $this->filePath]);
        }
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

