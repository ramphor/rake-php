<?php

namespace Rake\Manager;

use Rake\Contracts\CompletionAction\CompletionActionInterface;

/**
 * Completion Action Manager
 * 
 * Quản lý các completion actions - actions được thực thi khi hoàn thành crawl/processing.
 * Support đăng ký, execute, và lấy metadata cho UI.
 */
class CompletionActionManager
{
    /**
     * Registered completion actions
     * 
     * @var array<string, CompletionActionInterface>
     */
    private static $actions = [];

    /**
     * Action metadata for UI
     * 
     * @var array<string, array>
     */
    private static $actionMetadata = [];

    /**
     * Đăng ký completion action
     *
     * @param string $type Action type identifier
     * @param CompletionActionInterface|callable $action Action instance or factory
     * @param array $metadata Metadata cho UI (label, description, icon, etc.)
     * @return void
     */
    public static function register(string $type, $action, array $metadata = []): void
    {
        // Store action (instance or factory)
        self::$actions[$type] = $action;

        // Store metadata
        self::$actionMetadata[$type] = array_merge([
            'type' => $type,
            'label' => $metadata['label'] ?? ucfirst(str_replace('_', ' ', $type)),
            'description' => $metadata['description'] ?? '',
            'icon' => $metadata['icon'] ?? '🎯',
            'category' => $metadata['category'] ?? 'general',
            'enabled' => $metadata['enabled'] ?? true
        ], $metadata);
    }

    /**
     * Get action instance
     *
     * @param string $type
     * @return CompletionActionInterface|null
     */
    public static function getAction(string $type): ?CompletionActionInterface
    {
        if (!isset(self::$actions[$type])) {
            return null;
        }

        $action = self::$actions[$type];

        // If factory (callable), resolve it
        if (is_callable($action) && !$action instanceof CompletionActionInterface) {
            $action = $action();
            self::$actions[$type] = $action; // Cache resolved instance
        }

        return $action instanceof CompletionActionInterface ? $action : null;
    }

    /**
     * Execute completion action
     *
     * @param string $type Action type
     * @param array $context Execution context
     * @param array $config Action configuration
     * @return array
     */
    public static function execute(string $type, array $context, array $config = []): array
    {
        $action = self::getAction($type);

        if (!$action) {
            return [
                'success' => false,
                'message' => "Completion action not found: {$type}",
                'data' => null
            ];
        }

        // Set configuration
        if (!empty($config)) {
            $action->setConfig($config);
        }

        // Execute action
        try {
            $result = $action->execute($context);
            return $result;
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => "Completion action failed: " . $e->getMessage(),
                'data' => ['exception' => $e]
            ];
        }
    }

    /**
     * Execute multiple completion actions
     *
     * @param array $actions Array of ['type' => string, 'config' => array]
     * @param array $context Execution context
     * @return array
     */
    public static function executeMultiple(array $actions, array $context): array
    {
        $results = [];
        $successCount = 0;
        $failureCount = 0;

        foreach ($actions as $actionConfig) {
            $type = $actionConfig['type'] ?? null;
            $config = $actionConfig['config'] ?? [];

            if (!$type) {
                $failureCount++;
                $results[] = [
                    'type' => 'unknown',
                    'success' => false,
                    'message' => 'Action type not specified'
                ];
                continue;
            }

            $result = self::execute($type, $context, $config);
            $results[] = array_merge(['type' => $type], $result);

            if ($result['success']) {
                $successCount++;
            } else {
                $failureCount++;
            }
        }

        return [
            'success' => $failureCount === 0,
            'total' => count($actions),
            'success_count' => $successCount,
            'failure_count' => $failureCount,
            'results' => $results
        ];
    }

    /**
     * Get all registered actions
     *
     * @return array<string, CompletionActionInterface>
     */
    public static function getActions(): array
    {
        return self::$actions;
    }

    /**
     * Get action metadata cho UI
     *
     * @param string|null $type Specific action type or null for all
     * @return array
     */
    public static function getActionMetadata(?string $type = null): array
    {
        if ($type !== null) {
            return self::$actionMetadata[$type] ?? null;
        }

        return self::$actionMetadata;
    }

    /**
     * Get action metadata với config fields cho UI
     *
     * @param string $type Action type
     * @return array|null
     */
    public static function getActionMetadataWithConfigFields(string $type): ?array
    {
        $metadata = self::$actionMetadata[$type] ?? null;

        if (!$metadata) {
            return null;
        }

        // Get config fields from action class
        $action = self::getAction($type);
        if ($action) {
            $configFields = $action::getConfigFields();
            $metadata['configFields'] = $configFields;
        }

        return $metadata;
    }

    /**
     * Get all actions metadata cho UI (with config fields)
     *
     * @return array
     */
    public static function getAllForUI(): array
    {
        $uiData = [];

        foreach (self::$actionMetadata as $type => $metadata) {
            $action = self::getAction($type);
            
            if ($action) {
                $configFields = $action::getConfigFields();
                
                $uiData[] = array_merge($metadata, [
                    'configFields' => $configFields
                ]);
            } else {
                $uiData[] = $metadata;
            }
        }

        return $uiData;
    }

    /**
     * Check if action is registered
     *
     * @param string $type
     * @return bool
     */
    public static function hasAction(string $type): bool
    {
        return isset(self::$actions[$type]);
    }

    /**
     * Unregister action
     *
     * @param string $type
     * @return void
     */
    public static function unregister(string $type): void
    {
        unset(self::$actions[$type]);
        unset(self::$actionMetadata[$type]);
    }

    /**
     * Clear all registered actions (for testing)
     *
     * @return void
     */
    public static function clear(): void
    {
        self::$actions = [];
        self::$actionMetadata = [];
    }

    /**
     * Get registered class for action type
     *
     * @param string $type
     * @return string|null
     */
    public static function getRegisteredClass(string $type): ?string
    {
        $action = self::$actions[$type] ?? null;

        if ($action instanceof CompletionActionInterface) {
            return get_class($action);
        }

        if (is_callable($action)) {
            // Cannot determine class from callable without executing
            return null;
        }

        return null;
    }
}

