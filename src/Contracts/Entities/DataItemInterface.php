<?php

namespace Rake\Contracts\Entities;

/**
 * Base Data Item Interface
 * Contract for all data items (raw and parsed)
 */
interface DataItemInterface
{
    /**
     * Get data value by key
     * 
     * @param string $key Data key
     * @param mixed $default Default value if key not found
     * @return mixed
     */
    public function get(string $key, $default = null);

    /**
     * Set data value
     * 
     * @param string $key Data key
     * @param mixed $value Data value
     * @return self
     */
    public function set(string $key, $value): self;

    /**
     * Check if key exists
     * 
     * @param string $key Data key
     * @return bool
     */
    public function has(string $key): bool;

    /**
     * Get all data
     * 
     * @return array
     */
    public function getData(): array;

    /**
     * Check if this is a null item (failed operation)
     * 
     * @return bool
     */
    public function isNull(): bool;

    /**
     * Convert to array
     * 
     * @return array
     */
    public function toArray(): array;
}

