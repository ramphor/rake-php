<?php

namespace Rake\Entities\ParsedData;

use Rake\Contracts\Entities\ParsedDataItemInterface;


/**
 * Extracted Data Item
 * Represents a data item that has been extracted and can be processed
 * 
 * This entity carries data through the processor chain
 */
class ExtractedDataItem extends ParsedDataItem
{
    // All methods inherited from AbstractDataItem

    /**
     * Create from array
     * 
     * @param array $array Array with 'data' and optionally 'metadata'
     * @return self
     */
    public static function fromArray(array $array): self
    {
        return new self(
            $array['data'] ?? $array,
            $array['metadata'] ?? []
        );
    }

    /**
     * Magic getter
     * 
     * @param string $key
     * @return mixed
     */
    public function __get(string $key)
    {
        return $this->get($key);
    }

    /**
     * Magic setter
     * 
     * @param string $key
     * @param mixed $value
     */
    public function __set(string $key, $value)
    {
        $this->set($key, $value);
    }

    /**
     * Magic isset
     * 
     * @param string $key
     * @return bool
     */
    public function __isset(string $key)
    {
        return $this->has($key);
    }
}

