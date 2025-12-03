<?php

namespace Rake\Contracts\Entities;

/**
 * Raw Data Item Interface
 * Contract for raw data items loaded from database (dpc_rake_data_origins)
 */
interface RawDataItemInterface extends DataItemInterface
{
    /**
     * Get raw data from 'raw_data' column
     * 
     * @return mixed
     */
    public function getRawData();

    /**
     * Get origin ID from 'id' column
     * 
     * @return int|null
     */
    public function getOriginId(): ?int;

    /**
     * Check if raw data is HTML
     * 
     * @return bool
     */
    public function isHtml(): bool;

    /**
     * Check if raw data is JSON
     * 
     * @return bool
     */
    public function isJson(): bool;

    /**
     * Check if raw data is XML
     * 
     * @return bool
     */
    public function isXml(): bool;

    /**
     * Get parsed JSON if raw data is JSON
     * 
     * @return array|null
     */
    public function getAsJson(): ?array;

    /**
     * Create from dpc_rake_data_origins row
     * 
     * @param array $rawItem Database row
     * @return self
     */
    public static function fromOrigin(array $rawItem): self;
}

