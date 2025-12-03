<?php

namespace Rake\Entities\ParsedData;

use Rake\Contracts\Entities\ParsedDataItemInterface;

/**
 * Abstract Data Item
 * Base implementation for data items with ArrayAccess support
 */
abstract class ParsedDataItem implements ParsedDataItemInterface, \ArrayAccess
{
    /**
     * @var array Data payload
     */
    protected array $data;

    /**
     * @var array Metadata about the item
     */
    protected array $metadata;

    /**
     * Constructor
     * 
     * @param array $data Data payload
     * @param array $metadata Metadata (optional)
     */
    public function __construct(array $data = [], array $metadata = [])
    {
        $this->data = $data;
        $this->metadata = $metadata;
    }

    /**
     * Get data value by key
     */
    public function get(string $key, $default = null)
    {
        return $this->data[$key] ?? $default;
    }

    /**
     * Set data value
     */
    public function set(string $key, $value): ParsedDataItemInterface
    {
        $this->data[$key] = $value;
        return $this;
    }

    /**
     * Check if key exists
     */
    public function has(string $key): bool
    {
        return isset($this->data[$key]);
    }

    /**
     * Get all data
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Set all data
     */
    public function setData(array $data): ParsedDataItemInterface
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Merge data
     */
    public function mergeData(array $data): ParsedDataItemInterface
    {
        $this->data = array_merge($this->data, $data);
        return $this;
    }

    /**
     * Get metadata value
     */
    public function getMeta(string $key, $default = null)
    {
        return $this->metadata[$key] ?? $default;
    }

    /**
     * Set metadata value
     */
    public function setMeta(string $key, $value): ParsedDataItemInterface
    {
        $this->metadata[$key] = $value;
        return $this;
    }

    /**
     * Get all metadata
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    /**
     * Check if this is a null/invalid item
     * Default: false (valid item)
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
            'data' => $this->data,
            'metadata' => $this->metadata,
        ];
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
     * ArrayAccess: Check if offset exists
     * 
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists($offset): bool
    {
        return $this->has($offset);
    }

    /**
     * ArrayAccess: Get offset value
     * 
     * @param mixed $offset
     * @return mixed
     */
    public function offsetGet($offset): mixed
    {
        return $this->get($offset);
    }

    /**
     * ArrayAccess: Set offset value
     * 
     * @param mixed $offset
     * @param mixed $value
     * @return void
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
     * 
     * @param mixed $offset
     * @return void
     */
    public function offsetUnset($offset): void
    {
        unset($this->data[$offset]);
    }
}

