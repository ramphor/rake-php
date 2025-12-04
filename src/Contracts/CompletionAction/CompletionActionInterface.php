<?php

namespace Rake\Contracts\CompletionAction;

/**
 * Completion Action Interface
 * 
 * Contract cho các actions được thực thi khi hoàn thành crawl/processing.
 * Ví dụ: Send notification, generate report, cleanup, log summary, etc.
 */
interface CompletionActionInterface
{
    /**
     * Get action type identifier
     *
     * @return string Unique identifier (e.g., 'send_email', 'generate_report')
     */
    public function getType(): string;

    /**
     * Get action name (human-readable)
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Get action description
     *
     * @return string
     */
    public function getDescription(): string;

    /**
     * Execute completion action
     *
     * @param array $context Context data (processed items count, errors, duration, etc.)
     * @return array ['success' => bool, 'message' => string, 'data' => mixed]
     */
    public function execute(array $context): array;

    /**
     * Get configuration fields for UI
     *
     * @return array Array of field definitions
     */
    public static function getConfigFields(): array;

    /**
     * Set configuration
     *
     * @param array $config
     * @return self
     */
    public function setConfig(array $config): self;

    /**
     * Get configuration
     *
     * @return array
     */
    public function getConfig(): array;

    /**
     * Validate configuration
     *
     * @param array $config
     * @return array ['valid' => bool, 'errors' => array]
     */
    public static function validateConfig(array $config): array;
}

