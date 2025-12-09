<?php

namespace Rake\Actions;

/**
 * Action Result
 * 
 * Result object returned by action execution
 */
class ActionResult
{
    /**
     * @var bool Success status
     */
    private bool $success;

    /**
     * @var string|null Error message if failed
     */
    private ?string $error;

    /**
     * @var array Result data
     */
    private array $data;

    /**
     * @var array Metadata (execution time, etc.)
     */
    private array $metadata;

    /**
     * Constructor
     * 
     * @param bool $success Success status
     * @param string|null $error Error message
     * @param array $data Result data
     * @param array $metadata Metadata
     */
    public function __construct(
        bool $success = true,
        ?string $error = null,
        array $data = [],
        array $metadata = []
    ) {
        $this->success = $success;
        $this->error = $error;
        $this->data = $data;
        $this->metadata = $metadata;
    }

    /**
     * Create success result
     * 
     * @param array $data Result data
     * @param array $metadata Metadata
     * @return self
     */
    public static function success(array $data = [], array $metadata = []): self
    {
        return new self(true, null, $data, $metadata);
    }

    /**
     * Create error result
     * 
     * @param string $error Error message
     * @param array $data Additional data
     * @param array $metadata Metadata
     * @return self
     */
    public static function error(string $error, array $data = [], array $metadata = []): self
    {
        return new self(false, $error, $data, $metadata);
    }

    /**
     * Check if successful
     * 
     * @return bool
     */
    public function isSuccess(): bool
    {
        return $this->success;
    }

    /**
     * Get error message
     * 
     * @return string|null
     */
    public function getError(): ?string
    {
        return $this->error;
    }

    /**
     * Get result data
     * 
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Get metadata
     * 
     * @return array
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    /**
     * Get specific data value
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        return $this->data[$key] ?? $default;
    }
}

