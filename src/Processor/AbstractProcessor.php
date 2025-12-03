<?php

namespace Rake\Processor;

use Rake\Contracts\Processor\ProcessorInterface;
use Rake\Contracts\Entities\ParsedDataItemInterface;
use Rake\Entities\ParsedData\ExtractedDataItem;
use Rake\Entities\ParsedData\NullDataItem;

/**
 * Abstract Processor
 * Base class for all processors providing common functionality
 */
abstract class AbstractProcessor implements ProcessorInterface
{
    /**
     * @var array Processor configuration
     */
    protected array $config;

    /**
     * Constructor
     * 
     * @param array $config Processor configuration
     */
    public function __construct(array $config = [])
    {
        $this->config = $config;
        $this->initialize();
    }

    /**
     * Initialize processor
     * Can be overridden by child classes
     * 
     * @return void
     */
    protected function initialize(): void
    {
        // Default: do nothing
        // Child classes can override to set up resources
    }

    /**
     * Process data item
     * Must be implemented by child classes
     * 
     * @param ParsedDataItemInterface $item Input data item
     * @return ParsedDataItemInterface Processed item or NullDataItem if rejected/failed
     */
    abstract public function process(ParsedDataItemInterface $item): ParsedDataItemInterface;

    /**
     * Get configuration value
     * 
     * @param string $key Configuration key
     * @param mixed $default Default value if key not found
     * @return mixed
     */
    protected function getConfig(string $key, $default = null)
    {
        return $this->config[$key] ?? $default;
    }

    /**
     * Set configuration value
     * 
     * @param string $key Configuration key
     * @param mixed $value Configuration value
     * @return self
     */
    protected function setConfig(string $key, $value): self
    {
        $this->config[$key] = $value;
        return $this;
    }

    /**
     * Get all configuration
     * 
     * @return array
     */
    protected function getAllConfig(): array
    {
        return $this->config;
    }

    /**
     * Validate required fields in data item
     * 
     * @param ParsedDataItemInterface $item Data item to validate
     * @param array $requiredFields Required field names
     * @return void
     * @throws \RuntimeException If required field is missing
     */
    protected function validateRequiredFields(ParsedDataItemInterface $item, array $requiredFields): void
    {
        foreach ($requiredFields as $field) {
            if (!$item->has($field) || $item->get($field) === '') {
                throw new \RuntimeException("Required field '{$field}' is missing");
            }
        }
    }

    /**
     * Create ExtractedDataItem from array
     * Helper method for processors
     * 
     * @param array $data Data array
     * @param array $metadata Metadata array
     * @return ExtractedDataItem
     */
    protected function createItem(array $data, array $metadata = []): ExtractedDataItem
    {
        return new ExtractedDataItem($data, $metadata);
    }

    /**
     * Create NullDataItem with reason
     * Helper method for processors to return failed/rejected items
     * 
     * @param string $reason Failure/rejection reason
     * @return NullDataItem
     */
    protected function createNullItem(string $reason = 'Item rejected'): NullDataItem
    {
        return new NullDataItem($reason);
    }

    /**
     * Log message if logger available
     * 
     * @param string $message Log message
     * @param array $context Additional context
     * @return void
     */
    protected function log(string $message, array $context = []): void
    {
        if (class_exists('\Rake\Facade\Logger')) {
            \Rake\Facade\Logger::info($message, $context);
        }
    }

    /**
     * Log error if logger available
     * 
     * @param string $message Error message
     * @param array $context Additional context
     * @return void
     */
    protected function logError(string $message, array $context = []): void
    {
        if (class_exists('\Rake\Facade\Logger')) {
            \Rake\Facade\Logger::error($message, $context);
        }
    }
}

