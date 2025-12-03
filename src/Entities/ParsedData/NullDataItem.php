<?php

namespace Rake\Entities\ParsedData;

use Rake\Contracts\Entities\ParsedDataItemInterface;

/**
 * Null Data Item
 * Null Object Pattern - represents a failed/rejected data item
 * 
 * Used when a processor fails or rejects an item, allowing chain to continue
 * without null checks
 */
class NullDataItem extends ParsedDataItem
{
    /**
     * @var string Reason for null/failure
     */
    private string $reason;

    /**
     * Constructor
     * 
     * @param string $reason Reason for failure/rejection
     */
    public function __construct(string $reason = 'Item rejected')
    {
        $this->reason = $reason;
    }

    /**
     * Get data value by key (always returns default)
     */
    public function get(string $key, $default = null)
    {
        return $default;
    }

    /**
     * Set data value (no-op)
     */
    public function set(string $key, $value): self
    {
        return $this;
    }

    /**
     * Check if key exists (always false)
     */
    public function has(string $key): bool
    {
        return false;
    }

    /**
     * Get all data (always empty)
     */
    public function getData(): array
    {
        return [];
    }

    /**
     * Set all data (no-op)
     */
    public function setData(array $data): ParsedDataItemInterface
    {
        return $this;
    }

    /**
     * Merge data (no-op)
     */
    public function mergeData(array $data): ParsedDataItemInterface
    {
        return $this;
    }

    /**
     * Get metadata value (always returns default)
     */
    public function getMeta(string $key, $default = null)
    {
        return $default;
    }

    /**
     * Set metadata value (no-op)
     */
    public function setMeta(string $key, $value): ParsedDataItemInterface
    {
        return $this;
    }

    /**
     * Get all metadata (always empty)
     */
    public function getMetadata(): array
    {
        return [];
    }

    /**
     * Check if this is a null/invalid item (always true)
     */
    public function isNull(): bool
    {
        return true;
    }

    /**
     * Get failure reason
     * 
     * @return string
     */
    public function getReason(): string
    {
        return $this->reason;
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'null' => true,
            'reason' => $this->reason,
        ];
    }

    /**
     * Magic getter (always returns null)
     */
    public function __get(string $key)
    {
        return null;
    }

    /**
     * Magic setter (no-op)
     */
    public function __set(string $key, $value)
    {
        // No-op
    }

    /**
     * Magic isset (always false)
     */
    public function __isset(string $key)
    {
        return false;
    }

    /**
     * ArrayAccess: Check if offset exists (always false)
     */
    public function offsetExists($offset): bool
    {
        return false;
    }

    /**
     * ArrayAccess: Get offset value (always returns null)
     */
    public function offsetGet($offset): mixed
    {
        return null;
    }

    /**
     * ArrayAccess: Set offset value (no-op)
     */
    public function offsetSet($offset, $value): void
    {
        // No-op for NullDataItem
    }

    /**
     * ArrayAccess: Unset offset (no-op)
     */
    public function offsetUnset($offset): void
    {
        // No-op for NullDataItem
    }
}

