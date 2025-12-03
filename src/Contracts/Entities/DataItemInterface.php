<?php

namespace Rake\Contracts\Entities;

/**
 * Data Item Interface
 * Base contract for all data items flowing through processor chains
 */
interface DataItemInterface
{
    /**
     * Get data value by key
     * 
     * @param string $key Data key
     * @param mixed $default Default value
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
     * Check if this is a null/invalid item
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

