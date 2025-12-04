<?php

namespace Rake\CompletionAction;

use Rake\Contracts\CompletionAction\CompletionActionInterface;

/**
 * Abstract Completion Action
 * 
 * Base class cho tất cả completion actions.
 */
abstract class AbstractCompletionAction implements CompletionActionInterface
{
    /**
     * Action configuration
     *
     * @var array
     */
    protected $config = [];

    /**
     * Constructor
     *
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    /**
     * Set configuration
     *
     * @param array $config
     * @return self
     */
    public function setConfig(array $config): self
    {
        $this->config = array_merge($this->config, $config);
        return $this;
    }

    /**
     * Get configuration
     *
     * @return array
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * Get config value by key
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    protected function getConfigValue(string $key, $default = null)
    {
        return $this->config[$key] ?? $default;
    }

    /**
     * Validate configuration (default implementation)
     *
     * @param array $config
     * @return array
     */
    public static function validateConfig(array $config): array
    {
        // Default: always valid
        // Override in child classes for specific validation
        return [
            'valid' => true,
            'errors' => []
        ];
    }

    /**
     * Get default config fields (override in child classes)
     *
     * @return array
     */
    public static function getConfigFields(): array
    {
        return [];
    }

    /**
     * Abstract execute method
     */
    abstract public function execute(array $context): array;

    /**
     * Abstract getType method
     */
    abstract public function getType(): string;

    /**
     * Abstract getName method
     */
    abstract public function getName(): string;

    /**
     * Abstract getDescription method
     */
    abstract public function getDescription(): string;
}

