<?php

namespace Rake\DataSource;

use Rake\Contracts\DataSource\DataSourceInterface;

/**
 * Abstract Data Source
 * Base class for all data sources
 */
abstract class AbstractDataSource implements DataSourceInterface
{
    /**
     * @var array Source configuration
     */
    protected array $config;

    /**
     * @var string Source name
     */
    protected string $name;

    /**
     * Constructor
     * 
     * @param array $config Source configuration
     */
    public function __construct(array $config = [])
    {
        $this->config = $config;
        $this->name = $config['name'] ?? static::class;
    }

    /**
     * Fetch data from source
     * Must be implemented by child classes
     * 
     * @return array Array of raw data items
     */
    abstract public function fetch(): array;

    /**
     * Get source type
     * Must be implemented by child classes
     * 
     * @return string
     */
    abstract public function getType(): string;

    /**
     * Get source name
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get source configuration
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * Get configuration value
     * 
     * @param string $key Config key
     * @param mixed $default Default value
     * @return mixed
     */
    protected function getConfigValue(string $key, $default = null)
    {
        return $this->config[$key] ?? $default;
    }

    /**
     * Validate source configuration
     * Can be overridden by child classes
     * 
     * @return bool
     * @throws \RuntimeException If invalid
     */
    public function validate(): bool
    {
        // Default: always valid
        // Child classes should override to add validation
        return true;
    }

    /**
     * Log message
     * 
     * @param string $message Log message
     * @param array $context Context
     */
    protected function log(string $message, array $context = []): void
    {
        if (class_exists('\Rake\Facade\Logger')) {
            \Rake\Facade\Logger::info($message, array_merge($context, [
                'source' => $this->getName(),
                'type' => $this->getType(),
            ]));
        }
    }

    /**
     * Log error
     * 
     * @param string $message Error message
     * @param array $context Context
     */
    protected function logError(string $message, array $context = []): void
    {
        if (class_exists('\Rake\Facade\Logger')) {
            \Rake\Facade\Logger::error($message, array_merge($context, [
                'source' => $this->getName(),
                'type' => $this->getType(),
            ]));
        }
    }
}

