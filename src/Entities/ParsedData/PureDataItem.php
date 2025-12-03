<?php

namespace Rake\Entities\ParsedData;

/**
 * Pure Data Item
 * Wraps raw items without extraction when worker has NO parser
 * 
 * Different from RawDataItem (database entity):
 * - In-memory pure data wrapper
 * - Contains raw item data directly
 * - No database-specific fields
 * 
 * Usage: When worker has NO data parser configured
 */
class PureDataItem extends ParsedDataItem
{
    /**
     * @var mixed Original raw data
     */
    private $rawData;

    /**
     * Constructor
     * 
     * @param array $rawItem Raw item from dpc_rake_data_origins
     */
    public function __construct(array $rawItem)
    {
        // Store original raw data
        $this->rawData = $rawItem['raw_data'] ?? null;

        // Build data array with all fields from raw item
        $data = [
            'raw_data' => $this->rawData,
            'guid' => $rawItem['guid'] ?? null,
            'source_id' => $rawItem['source_id'] ?? null,
            'fetched_at' => $rawItem['fetched_at'] ?? null,
            'origin_id' => $rawItem['id'] ?? null,
        ];

        // Metadata
        $metadata = [
            'type' => 'pure',
            'has_parser' => false,
        ];

        parent::__construct($data, $metadata);
    }

    /**
     * Get original raw data
     * 
     * @return mixed
     */
    public function getRawData()
    {
        return $this->rawData;
    }

    /**
     * Check if raw data is HTML
     * 
     * @return bool
     */
    public function isHtml(): bool
    {
        if (!is_string($this->rawData)) {
            return false;
        }

        return strpos($this->rawData, '<') !== false && strpos($this->rawData, '>') !== false;
    }

    /**
     * Check if raw data is JSON
     * 
     * @return bool
     */
    public function isJson(): bool
    {
        if (!is_string($this->rawData)) {
            return false;
        }

        json_decode($this->rawData);
        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * Check if raw data is XML
     * 
     * @return bool
     */
    public function isXml(): bool
    {
        if (!is_string($this->rawData)) {
            return false;
        }

        return strpos($this->rawData, '<?xml') !== false;
    }

    /**
     * Get parsed JSON if raw data is JSON
     * 
     * @return array|null
     */
    public function getAsJson(): ?array
    {
        if (!$this->isJson()) {
            return null;
        }

        return json_decode($this->rawData, true);
    }

    /**
     * Create from dpc_rake_data_origins row
     * 
     * @param array $rawItem Row from dpc_rake_data_origins
     * @return self
     */
    public static function fromRawItem(array $rawItem): self
    {
        return new self($rawItem);
    }
}

