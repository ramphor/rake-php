<?php

namespace Rake\Contracts\DataSource;

/**
 * Data Source Interface
 * Contract for data sources that fetch raw data
 */
interface DataSourceInterface
{
    /**
     * Fetch data from source
     * 
     * @return array Array of raw data items
     * @throws \RuntimeException If fetch fails
     */
    public function fetch(): array;

    /**
     * Get source type
     * 
     * @return string Type identifier (url, csv, xml, mysql, etc.)
     */
    public function getType(): string;

    /**
     * Get source name
     * 
     * @return string Human-readable name
     */
    public function getName(): string;

    /**
     * Get source configuration
     * 
     * @return array
     */
    public function getConfig(): array;

    /**
     * Validate source configuration
     * 
     * @return bool True if valid
     * @throws \RuntimeException If invalid
     */
    public function validate(): bool;
}

