<?php

namespace Rake\Manager;

use Rake\Contracts\Processor\ProcessorInterface;

/**
 * Processor Manager
 * Manages processor registration and retrieval
 * 
 * Processors transform and process data according to ProcessorInterface contract
 */
class ProcessorManager
{
    /**
     * @var array Registered processors
     */
    private static array $processors = [];

    /**
     * @var array Processor aliases
     */
    private static array $aliases = [];

    /**
     * Register a processor
     *
     * @param string $type Processor type identifier
     * @param ProcessorInterface|string $processor Processor instance or class name
     * @param array $config Default configuration
     * @return void
     */
    public static function register(string $type, ProcessorInterface|string $processor, array $config = []): void
    {
        self::$processors[$type] = [
            'processor' => $processor,
            'config' => $config,
        ];
    }

    /**
     * Register processor with alias
     *
     * @param string $alias Processor alias
     * @param string $type Original processor type
     * @return void
     */
    public static function alias(string $alias, string $type): void
    {
        self::$aliases[$alias] = $type;
    }

    /**
     * Get processor by type
     *
     * @param string $type Processor type
     * @param array $options Runtime options to merge with default config
     * @return ProcessorInterface
     * @throws \RuntimeException If processor not found
     */
    public function getProcessor(string $type, array $options = []): ProcessorInterface
    {
        // Resolve alias
        $resolvedType = self::$aliases[$type] ?? $type;

        if (!isset(self::$processors[$resolvedType])) {
            throw new \RuntimeException("Processor '{$type}' not registered");
        }

        $processorData = self::$processors[$resolvedType];
        $processor = $processorData['processor'];
        $defaultConfig = $processorData['config'];

        // Merge configurations
        $config = array_merge($defaultConfig, $options);

        // Instantiate if class name
        if (is_string($processor)) {
            if (!class_exists($processor)) {
                throw new \RuntimeException("Processor class '{$processor}' not found");
            }

            return new $processor($config);
        }

        // Return instance (clone to avoid state sharing)
        if ($processor instanceof ProcessorInterface) {
            return clone $processor;
        }

        throw new \RuntimeException("Invalid processor registered for type '{$type}'");
    }

    /**
     * Check if processor is registered
     *
     * @param string $type Processor type
     * @return bool
     */
    public static function has(string $type): bool
    {
        $resolvedType = self::$aliases[$type] ?? $type;
        return isset(self::$processors[$resolvedType]);
    }

    /**
     * Get all registered processor types
     *
     * @return array
     */
    public static function getRegisteredTypes(): array
    {
        return array_keys(self::$processors);
    }

    /**
     * Get all registered processors
     *
     * @return array
     */
    public static function getAll(): array
    {
        return self::$processors;
    }

    /**
     * Clear all registered processors (for testing)
     *
     * @return void
     */
    public static function clearAll(): void
    {
        self::$processors = [];
        self::$aliases = [];
    }

    /**
     * Create processor chain from config
     *
     * @param array $processorConfigs Array of processor configs
     * @return ProcessorInterface[] Array of ProcessorInterface instances
     * @throws \RuntimeException If any processor fails to create
     */
    public function createChain(array $processorConfigs): array
    {
        $chain = [];

        foreach ($processorConfigs as $config) {
            $type = $config['type'] ?? null;
            
            if (!$type) {
                throw new \RuntimeException('Processor type is required in config');
            }

            $settings = $config['settings'] ?? [];
            $processor = $this->getProcessor($type, $settings);
            
            $chain[] = $processor;
        }

        return $chain;
    }

    /**
     * Execute processor chain
     *
     * @param ProcessorInterface[] $processors Array of ProcessorInterface instances
     * @param array $data Input data
     * @return \Rake\Contracts\Entities\ParsedDataItemInterface Processed item (can be NullDataItem if failed)
     * @throws \RuntimeException If processor in chain is invalid
     */
    public function executeChain(array $processors, array $data): \Rake\Contracts\Entities\ParsedDataItemInterface
    {
        // Convert array to ExtractedDataItem
        $item = \Rake\Entities\ParsedData\ExtractedDataItem::fromArray($data);

        foreach ($processors as $processor) {
            if (!$processor instanceof ProcessorInterface) {
                throw new \RuntimeException(
                    'Invalid processor in chain: ' . get_class($processor)
                );
            }

            // Process data through processor
            $item = $processor->process($item);

            // Continue even if NullDataItem (other processors might handle it)
            // But typically if one returns NullDataItem, chain should stop
            if ($item->isNull()) {
                return $item; // Return NullDataItem, stop chain
            }
        }

        return $item;
    }
}
