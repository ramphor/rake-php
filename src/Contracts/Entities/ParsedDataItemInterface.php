<?php

namespace Rake\Contracts\Entities;

/**
 * Parsed Data Item Interface
 * Contract for parsed/extracted data items (in-memory)
 */
interface ParsedDataItemInterface extends DataItemInterface
{
    // Inherits: get(), set(), has(), getData(), isNull(), toArray()

    /**
     * Set all data
     * 
     * @param array $data Data to set
     * @return self
     */
    public function setData(array $data): self;

    /**
     * Merge data
     * 
     * @param array $data Data to merge
     * @return self
     */
    public function mergeData(array $data): self;

    /**
     * Get metadata value
     * 
     * @param string $key Metadata key
     * @param mixed $default Default value
     * @return mixed
     */
    public function getMeta(string $key, $default = null);

    /**
     * Set metadata value
     * 
     * @param string $key Metadata key
     * @param mixed $value Metadata value
     * @return self
     */
    public function setMeta(string $key, $value): self;

    /**
     * Get all metadata
     * 
     * @return array
     */
    public function getMetadata(): array;
}

