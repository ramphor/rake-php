<?php

namespace Rake\Actions;

/**
 * Context Action Manager
 * 
 * Registry pattern for managing and executing context actions
 * Supports actions for phase1, phase2, phase3 contexts
 */
class ContextActionManager
{
    /**
     * @var array Registered actions by phase context
     * Structure: ['phase1' => [actionId => action], 'phase1_extra_actions' => [...], 'phase2' => [...], 'phase3' => [...]]
     */
    private static array $actions = [
        'phase1' => [],
        'phase1_extra_actions' => [], // Data source scoped Phase 1 extra actions (bonus per data source)
        'phase2' => [],
        'phase3' => [],
    ];

    /**
     * Register an action
     * 
     * Actions are automatically registered for all their supported contexts
     * 
     * @param ContextActionInterface $action Action instance
     * @return void
     */
    public static function register(ContextActionInterface $action): void
    {
        $actionId = $action->getId();
        $supportedContexts = $action->getSupportedContexts();

        foreach ($supportedContexts as $phase) {
            if (!in_array($phase, ['phase1', 'phase1_extra_actions', 'phase2', 'phase3'])) {
                error_log("Rake ContextActionManager: Invalid phase '{$phase}' in supported contexts for action '{$actionId}'");
                continue;
            }

            // Check if already registered for this phase
            if (isset(self::$actions[$phase][$actionId])) {
                error_log("Rake ContextActionManager: Action '{$actionId}' already registered for {$phase}");
                continue;
            }

            self::$actions[$phase][$actionId] = $action;
            error_log("Rake ContextActionManager: Registered action '{$actionId}' for {$phase}");
        }
    }

    /**
     * Unregister an action
     * 
     * @param string $actionId Action ID
     * @param string|null $phase Optional: specific phase, or null to unregister from all phases
     * @return void
     */
    public static function unregister(string $actionId, ?string $phase = null): void
    {
        if ($phase !== null) {
            if (isset(self::$actions[$phase][$actionId])) {
                unset(self::$actions[$phase][$actionId]);
                error_log("Rake ContextActionManager: Unregistered action '{$actionId}' from {$phase}");
            }
        } else {
            // Unregister from all phases
            foreach (['phase1', 'phase1_extra_actions', 'phase2', 'phase3'] as $p) {
                if (isset(self::$actions[$p][$actionId])) {
                    unset(self::$actions[$p][$actionId]);
                }
            }
            error_log("Rake ContextActionManager: Unregistered action '{$actionId}' from all phases");
        }
    }

    /**
     * Execute all actions for a specific phase
     * 
     * @param string $phase Phase context: 'phase1', 'phase2', or 'phase3'
     * @param ActionContext $context Action context
     * @return array Execution results keyed by action ID
     */
    public static function execute(string $phase, ActionContext $context): array
    {
        if (!in_array($phase, ['phase1', 'phase1_extra_actions', 'phase2', 'phase3'])) {
            error_log("Rake ContextActionManager: Invalid phase '{$phase}'");
            return [];
        }

        $actions = self::$actions[$phase] ?? [];
        if (empty($actions)) {
            error_log("Rake ContextActionManager: No actions registered for {$phase}");
            return [];
        }

        // Sort by priority (lower priority = higher priority)
        uasort($actions, function($a, $b) {
            $priorityA = $a->getPriority();
            $priorityB = $b->getPriority();
            return $priorityA <=> $priorityB;
        });

        $results = [];
        $startTime = microtime(true);

        foreach ($actions as $actionId => $action) {
            // Check if action should execute
            if (!$action->shouldExecute($context)) {
                error_log("Rake ContextActionManager: Action '{$actionId}' skipped (shouldExecute returned false)");
                continue;
            }

            try {
                $actionStartTime = microtime(true);
                error_log("Rake ContextActionManager: Executing action '{$actionId}' for {$phase}");

                $result = $action->execute($context);

                $actionEndTime = microtime(true);
                $executionTime = round(($actionEndTime - $actionStartTime) * 1000, 2); // milliseconds

                $results[$actionId] = [
                    'success' => $result->isSuccess(),
                    'error' => $result->getError(),
                    'data' => $result->getData(),
                    'metadata' => array_merge($result->getMetadata(), [
                        'execution_time_ms' => $executionTime,
                        'action_label' => $action->getLabel(),
                    ]),
                ];

                if ($result->isSuccess()) {
                    error_log("Rake ContextActionManager: Action '{$actionId}' completed successfully in {$executionTime}ms");
                } else {
                    error_log("Rake ContextActionManager: Action '{$actionId}' failed - " . $result->getError());
                }
            } catch (\Exception $e) {
                $results[$actionId] = [
                    'success' => false,
                    'error' => $e->getMessage(),
                    'data' => [],
                    'metadata' => [
                        'exception' => get_class($e),
                        'trace' => $e->getTraceAsString(),
                    ],
                ];
                error_log("Rake ContextActionManager: Action '{$actionId}' threw exception - " . $e->getMessage());
            }
        }

        $totalTime = round((microtime(true) - $startTime) * 1000, 2);
        error_log("Rake ContextActionManager: Executed " . count($results) . " action(s) for {$phase} in {$totalTime}ms");

        return $results;
    }

    /**
     * Get all registered actions for a phase
     * 
     * @param string $phase Phase context
     * @return array Array of ContextActionInterface instances
     */
    public static function getActions(string $phase): array
    {
        return self::$actions[$phase] ?? [];
    }

    /**
     * Check if an action is registered for a phase
     * 
     * @param string $phase Phase context
     * @param string $actionId Action ID
     * @return bool
     */
    public static function hasAction(string $phase, string $actionId): bool
    {
        return isset(self::$actions[$phase][$actionId]);
    }

    /**
     * Get action by ID and phase
     * 
     * @param string $phase Phase context
     * @param string $actionId Action ID
     * @return ContextActionInterface|null
     */
    public static function getAction(string $phase, string $actionId): ?ContextActionInterface
    {
        return self::$actions[$phase][$actionId] ?? null;
    }

    /**
     * Clear all actions for a phase
     * 
     * @param string $phase Phase context
     * @return void
     */
    public static function clear(string $phase): void
    {
        self::$actions[$phase] = [];
        error_log("Rake ContextActionManager: Cleared all actions for {$phase}");
    }

    /**
     * Clear all actions for all phases
     * 
     * @return void
     */
    public static function clearAll(): void
    {
        self::$actions = [
            'phase1' => [],
            'phase1_extra_actions' => [],
            'phase2' => [],
            'phase3' => [],
        ];
        error_log("Rake ContextActionManager: Cleared all actions");
    }
}

