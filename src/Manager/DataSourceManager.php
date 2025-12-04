<?php

namespace Rake\Manager;

use Rake\Contracts\DataSource\DataSourceInterface;

/**
 * Data Source Manager
 * Manages data source registration and retrieval
 * 
 * Data sources fetch raw data from various sources:
 * - URL sources (web pages)
 * - CSV sources (files, uploads)
 * - XML sources (feeds, APIs)
 * - MySQL sources (external databases)
 * - API sources (REST, GraphQL)
 */
class DataSourceManager
{
    /**
     * @var array Registered data source types
     */
    private static array $dataSourceTypes = [];

    /**
     * @var array Data source instances
     */
    private array $instances = [];

    /**
     * Register a data source type
     *
     * @param string $type Source type identifier (url, csv, xml, mysql, etc.)
     * @param string $className Data source class name
     * @return void
     */
    public static function registerType(string $type, string $className): void
    {
        if (!class_exists($className)) {
            throw new \RuntimeException("Data source class '{$className}' not found");
        }

        if (!is_subclass_of($className, DataSourceInterface::class)) {
            throw new \RuntimeException(
                "Data source class '{$className}' must implement DataSourceInterface"
            );
        }

        self::$dataSourceTypes[$type] = $className;
    }

    /**
     * Create data source instance
     *
     * @param string $type Source type
     * @param array $config Source configuration
     * @return DataSourceInterface
     * @throws \RuntimeException If type not registered
     */
    public function create(string $type, array $config): DataSourceInterface
    {
        if (!isset(self::$dataSourceTypes[$type])) {
            throw new \RuntimeException("Data source type '{$type}' not registered");
        }

        $className = self::$dataSourceTypes[$type];
        $instance = new $className($config);

        // Validate configuration
        $instance->validate();

        return $instance;
    }

    /**
     * Create and cache data source instance
     *
     * @param string $name Instance name
     * @param string $type Source type
     * @param array $config Source configuration
     * @return DataSourceInterface
     */
    public function createNamed(string $name, string $type, array $config): DataSourceInterface
    {
        if (isset($this->instances[$name])) {
            return $this->instances[$name];
        }

        $instance = $this->create($type, $config);
        $this->instances[$name] = $instance;

        return $instance;
    }

    /**
     * Get cached instance by name
     *
     * @param string $name Instance name
     * @return DataSourceInterface|null
     */
    public function getInstance(string $name): ?DataSourceInterface
    {
        return $this->instances[$name] ?? null;
    }

    /**
     * Check if type is registered
     *
     * @param string $type Source type
     * @return bool
     */
    public static function hasType(string $type): bool
    {
        return isset(self::$dataSourceTypes[$type]);
    }

    /**
     * Get all registered types
     *
     * @return array
     */
    public static function getRegisteredTypes(): array
    {
        return array_keys(self::$dataSourceTypes);
    }

    /**
     * Get registered class name for a type
     *
     * @param string $type Source type
     * @return string|null
     */
    public static function getRegisteredClass(string $type): ?string
    {
        return self::$dataSourceTypes[$type] ?? null;
    }

    /**
     * Get all instances
     *
     * @return array
     */
    public function getAllInstances(): array
    {
        return $this->instances;
    }

    /**
     * Clear all instances (for testing)
     *
     * @return void
     */
    public function clearInstances(): void
    {
        $this->instances = [];
    }

    /**
     * Clear all registered types (for testing)
     *
     * @return void
     */
    public static function clearTypes(): void
    {
        self::$dataSourceTypes = [];
    }

    /**
     * Fetch from multiple sources
     *
     * @param array $sources Array of source configs: [['type' => 'url', 'config' => [...]], ...]
     * @return array Combined results from all sources
     */
    public function fetchFromMultiple(array $sources): array
    {
        $allData = [];

        foreach ($sources as $sourceConfig) {
            $type = $sourceConfig['type'] ?? null;
            $config = $sourceConfig['config'] ?? [];

            if (!$type) {
                continue;
            }

            try {
                $source = $this->create($type, $config);
                $data = $source->fetch();

                $allData = array_merge($allData, $data);
            } catch (\Exception $e) {
                // Log error but continue with other sources
                if (class_exists('\Rake\Facade\Logger')) {
                    \Rake\Facade\Logger::error('Failed to fetch from source', [
                        'type' => $type,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        return $allData;
    }
}

