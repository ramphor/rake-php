<?php

namespace Rake\Actions;

/**
 * Abstract Context Action
 * 
 * Base class for context actions providing common functionality
 */
abstract class AbstractContextAction implements ContextActionInterface
{
    /**
     * @var string Action ID
     */
    protected string $id;

    /**
     * @var string Action label
     */
    protected string $label;

    /**
     * @var string Action description
     */
    protected string $description;

    /**
     * @var array Supported contexts (phases)
     */
    protected array $supportedContexts;

    /**
     * @var int Priority (lower = higher priority)
     */
    protected int $priority;

    /**
     * Constructor
     * 
     * @param string $id Action ID
     * @param string $label Action label
     * @param string $description Action description
     * @param array $supportedContexts Supported contexts: ['phase1', 'phase2', 'phase3']
     * @param int $priority Priority (default: 10)
     */
    public function __construct(
        string $id,
        string $label,
        string $description = '',
        array $supportedContexts = ['phase1'],
        int $priority = 10
    ) {
        $this->id = $id;
        $this->label = $label;
        $this->description = $description;
        $this->supportedContexts = $supportedContexts;
        $this->priority = $priority;
    }

    /**
     * Get action ID
     * 
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Get action label
     * 
     * @return string
     */
    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * Get action description
     * 
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Get supported contexts
     * 
     * @return array
     */
    public function getSupportedContexts(): array
    {
        return $this->supportedContexts;
    }

    /**
     * Get priority
     * 
     * @return int
     */
    public function getPriority(): int
    {
        return $this->priority;
    }

    /**
     * Check if action should be executed
     * 
     * Default: Check if phase is in supported contexts
     * Override in child classes for custom logic
     * 
     * @param ActionContext $context
     * @return bool
     */
    public function shouldExecute(ActionContext $context): bool
    {
        return in_array($context->getPhase(), $this->supportedContexts);
    }

    /**
     * Log action execution
     * 
     * @param string $message
     * @param array $context
     * @return void
     */
    protected function log(string $message, array $context = []): void
    {
        $context['action_id'] = $this->id;
        $context['action_label'] = $this->label;
        error_log("Rake Action [{$this->id}]: {$message} " . json_encode($context));
    }

    /**
     * Log error
     * 
     * @param string $message
     * @param array $context
     * @return void
     */
    protected function logError(string $message, array $context = []): void
    {
        $this->log("ERROR: {$message}", $context);
    }
}

