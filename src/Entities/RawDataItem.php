<?php

namespace Rake\Entities;

use Rake\Contracts\Entities\RawDataItemInterface;

/**
 * Raw Data Item
 * Represents raw data loaded directly from database table dpc_rake_data_origins
 * 
 * Table Structure (dpc_rake_data_origins):
 * - id: int (primary key)
 * - source_id: int (foreign key to dpc_rake_data_sources)
 * - guid: string (unique identifier)
 * - raw_data: text (the actual raw data - HTML/JSON/XML/etc)
 * - fetched_at: datetime
 * 
 * Used when worker has NO data extractor - wraps database row directly
 */
class RawDataItem implements RawDataItemInterface, \ArrayAccess
{
    /**
     * @var array Original database row
     */
    private array $rawItem;

    /**
     * @var mixed Raw data from 'raw_data' column
     */
    private $rawData;

    /**
     * @var int|null Origin ID from dpc_rake_data_origins.id
     */
    private ?int $originId;

    /**
     * Constructor
     * 
     * @param array $rawItem Raw item row from dpc_rake_data_origins
     */
    public function __construct(array $rawItem)
    {
        $this->rawItem = $rawItem;
        $this->originId = $rawItem['id'] ?? null;
        $this->rawData = $rawItem['raw_data'] ?? null;
    }

    /**
     * Get raw data
     * 
     * @return mixed
     */
    public function getRawData()
    {
        return $this->rawData;
    }

    /**
     * Get origin ID from dpc_rake_data_origins
     * 
     * @return int|null
     */
    public function getOriginId(): ?int
    {
        return $this->originId;
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
     * Get data value by key (from database row)
     */
    public function get(string $key, $default = null)
    {
        return $this->rawItem[$key] ?? $default;
    }

    /**
     * Set data value (updates in-memory, not DB)
     */
    public function set(string $key, $value): self
    {
        $this->rawItem[$key] = $value;
        return $this;
    }

    /**
     * Check if key exists in database row
     */
    public function has(string $key): bool
    {
        return isset($this->rawItem[$key]);
    }

    /**
     * Get all data (database row)
     */
    public function getData(): array
    {
        return $this->rawItem;
    }

    /**
     * Set all data
     */
    public function setData(array $data): self
    {
        $this->rawItem = $data;
        return $this;
    }

    /**
     * Merge data
     */
    public function mergeData(array $data): self
    {
        $this->rawItem = array_merge($this->rawItem, $data);
        return $this;
    }

    /**
     * Get metadata value (simulated from DB fields)
     */
    public function getMeta(string $key, $default = null)
    {
        $metadata = [
            'type' => 'raw',
            'origin_id' => $this->originId,
            'source_table' => 'dpc_rake_data_origins',
            'source_id' => $this->rawItem['source_id'] ?? null,
            'fetched_at' => $this->rawItem['fetched_at'] ?? null,
        ];
        
        return $metadata[$key] ?? $default;
    }

    /**
     * Set metadata value (no-op for RawDataItem)
     */
    public function setMeta(string $key, $value): self
    {
        // RawDataItem doesn't store custom metadata
        // It uses DB fields as metadata
        return $this;
    }

    /**
     * Get all metadata
     */
    public function getMetadata(): array
    {
        return [
            'type' => 'raw',
            'origin_id' => $this->originId,
            'source_table' => 'dpc_rake_data_origins',
            'source_id' => $this->rawItem['source_id'] ?? null,
            'fetched_at' => $this->rawItem['fetched_at'] ?? null,
        ];
    }

    /**
     * Check if this is a null/invalid item
     */
    public function isNull(): bool
    {
        return false;
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'data' => $this->rawItem,
            'metadata' => $this->getMetadata(),
        ];
    }

    /**
     * ArrayAccess: Check if offset exists
     */
    public function offsetExists($offset): bool
    {
        return $this->has($offset);
    }

    /**
     * ArrayAccess: Get offset value
     */
    public function offsetGet($offset): mixed
    {
        return $this->get($offset);
    }

    /**
     * ArrayAccess: Set offset value
     */
    public function offsetSet($offset, $value): void
    {
        if ($offset === null) {
            throw new \InvalidArgumentException('Cannot use null offset for data item');
        }
        
        $this->set($offset, $value);
    }

    /**
     * ArrayAccess: Unset offset
     */
    public function offsetUnset($offset): void
    {
        unset($this->rawItem[$offset]);
    }

    /**
     * Magic getter
     */
    public function __get(string $key)
    {
        return $this->get($key);
    }

    /**
     * Magic setter
     */
    public function __set(string $key, $value)
    {
        $this->set($key, $value);
    }

    /**
     * Magic isset
     */
    public function __isset(string $key)
    {
        return $this->has($key);
    }

    /**
     * Create from dpc_rake_data_origins row
     * 
     * @param array $rawItem Row from dpc_rake_data_origins table
     * @return self
     */
    public static function fromOrigin(array $rawItem): self
    {
        return new self($rawItem);
    }
}

