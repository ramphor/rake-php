<?php

namespace Rake\Actions;

/**
 * Action Context
 * 
 * Context object passed to actions containing phase information, project data, results, etc.
 */
class ActionContext
{
    /**
     * @var string Phase context: 'phase1', 'phase2', or 'phase3'
     */
    private string $phase;

    /**
     * @var int Project ID
     */
    private int $projectId;

    /**
     * @var array Phase execution results
     */
    private array $results;

    /**
     * @var array Flow configuration
     */
    private array $flowConfig;

    /**
     * @var array Additional context data
     */
    private array $data;

    /**
     * Constructor
     * 
     * @param string $phase Phase context
     * @param int $projectId Project ID
     * @param array $results Phase execution results
     * @param array $flowConfig Flow configuration
     * @param array $data Additional context data
     */
    public function __construct(
        string $phase,
        int $projectId,
        array $results = [],
        array $flowConfig = [],
        array $data = []
    ) {
        $this->phase = $phase;
        $this->projectId = $projectId;
        $this->results = $results;
        $this->flowConfig = $flowConfig;
        $this->data = $data;
    }

    /**
     * Get phase context
     * 
     * @return string
     */
    public function getPhase(): string
    {
        return $this->phase;
    }

    /**
     * Get project ID
     * 
     * @return int
     */
    public function getProjectId(): int
    {
        return $this->projectId;
    }

    /**
     * Get phase execution results
     * 
     * @return array
     */
    public function getResults(): array
    {
        return $this->results;
    }

    /**
     * Get flow configuration
     * 
     * @return array
     */
    public function getFlowConfig(): array
    {
        return $this->flowConfig;
    }

    /**
     * Get additional context data
     * 
     * @param string|null $key Optional key to get specific data
     * @param mixed $default Default value if key not found
     * @return mixed
     */
    public function getData(?string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->data;
        }
        return $this->data[$key] ?? $default;
    }

    /**
     * Set additional context data
     * 
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function setData(string $key, $value): void
    {
        $this->data[$key] = $value;
    }
}

