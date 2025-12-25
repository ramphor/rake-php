<?php

namespace Rake\Workers;

use Rake\Workers\Worker;

/**
 * WorkerManager for managing worker collection from project config
 */
class WorkerManager
{
    private $workers = [];

    public function __construct(array $flowConfig = null)
    {
        if ($flowConfig) {
            $this->loadFromFlowConfig($flowConfig);
        }
    }

    /**
     * Load workers from flow configuration
     */
    public function loadFromFlowConfig(array $flowConfig): void
    {
        $this->workers = [];

        if (!isset($flowConfig['nodes'])) {
            return;
        }

        foreach ($flowConfig['nodes'] as $node) {
            if ($node['type'] === 'worker' && ($node['data']['enabled'] ?? true)) {
                $worker = new Worker($node['data']);
                $this->workers[$worker->getId()] = $worker;
            }
        }
    }

    /**
     * Get all workers
     */
    public function getAll(): array
    {
        return $this->workers;
    }

    /**
     * Get worker by ID
     */
    public function get(string $id): ?Worker
    {
        return $this->workers[$id] ?? null;
    }

    /**
     * Get workers that can handle archive pages
     */
    public function getArchiveWorkers(): array
    {
        return array_filter($this->workers, function (Worker $worker) {
            return $worker->canHandleArchive();
        });
    }

    /**
     * Get workers that match a specific URL
     */
    public function getWorkersForUrl(string $url): array
    {
        return array_filter($this->workers, function (Worker $worker) use ($url) {
            return $worker->matchesUrl($url);
        });
    }

    /**
     * Get workers sorted by priority (highest first)
     */
    public function getByPriority(): array
    {
        $workers = $this->workers;
        uasort($workers, function (Worker $a, Worker $b) {
            return $b->getPriority() <=> $a->getPriority();
        });
        return $workers;
    }

    /**
     * Find best worker for a given URL based on priority and matching rules
     */
    public function findBestWorkerForUrl(string $url): ?Worker
    {
        $matchingWorkers = $this->getWorkersForUrl($url);
        
        if (empty($matchingWorkers)) {
            return null;
        }

        // Sort by priority and return the highest priority worker
        uasort($matchingWorkers, function (Worker $a, Worker $b) {
            return $b->getPriority() <=> $a->getPriority();
        });

        return reset($matchingWorkers);
    }

    /**
     * Find workers that can detect URLs from archive pages
     */
    public function findArchiveWorkersForPage(string $pageUrl): array
    {
        // For category pages, we want workers that can detect product URLs
        if (strpos($pageUrl, '/products_list/') !== false) {
            return $this->getWorkersForUrl('/products_detail/');
        }

        return $this->getArchiveWorkers();
    }

    /**
     * Add a worker
     */
    public function add(Worker $worker): void
    {
        $this->workers[$worker->getId()] = $worker;
    }

    /**
     * Remove a worker
     */
    public function remove(string $id): bool
    {
        if (isset($this->workers[$id])) {
            unset($this->workers[$id]);
            return true;
        }
        return false;
    }

    /**
     * Count workers
     */
    public function count(): int
    {
        return count($this->workers);
    }

    /**
     * Get worker IDs
     */
    public function getIds(): array
    {
        return array_keys($this->workers);
    }

    /**
     * Check if worker exists
     */
    public function has(string $id): bool
    {
        return isset($this->workers[$id]);
    }

    /**
     * Clear all workers
     */
    public function clear(): void
    {
        $this->workers = [];
    }

    /**
     * Get workers as array for logging/debugging
     */
    public function toArray(): array
    {
        $result = [];
        foreach ($this->workers as $worker) {
            $result[] = $worker->toArray();
        }
        return $result;
    }
}
