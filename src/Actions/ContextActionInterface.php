<?php

namespace Rake\Actions;

/**
 * Context Action Interface
 * 
 * Interface for actions that execute with context (phase 1, phase 2, phase 3)
 */
interface ContextActionInterface
{
    /**
     * Execute action with context
     * 
     * @param ActionContext $context Context containing phase, project_id, results, etc.
     * @return ActionResult Result of action execution
     */
    public function execute(ActionContext $context): ActionResult;

    /**
     * Get action ID
     * 
     * @return string
     */
    public function getId(): string;

    /**
     * Get action label
     * 
     * @return string
     */
    public function getLabel(): string;

    /**
     * Get action description
     * 
     * @return string
     */
    public function getDescription(): string;

    /**
     * Get supported contexts (phases)
     * 
     * @return array Array of phase names: ['phase1', 'phase2', 'phase3']
     */
    public function getSupportedContexts(): array;

    /**
     * Check if action should be executed (based on context)
     * 
     * @param ActionContext $context
     * @return bool
     */
    public function shouldExecute(ActionContext $context): bool;
}

