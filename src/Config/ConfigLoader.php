<?php

namespace Rake\Config;

use Rake\Config\DatabaseConfig;

/**
 * ConfigLoader: Load cấu hình từ file PHP trả về mảng, có thể mở rộng cho DB/env.
 */
class ConfigLoader
{
    /**
     * @var array
     */
    private static $config = [];

    /**
     * @var DatabaseConfig|null
     */
    private static $databaseConfig = null;

    /**
     * Load config từ file PHP trả về mảng
     * @param string|null $path Đường dẫn file config, mặc định là rake.config.php ở project root
     * @return array
     */
    public static function load(string $path = null): array
    {
        if ($path === null) {
            $path = dirname(__DIR__, 2) . '/rake.config.php';
        }
        if (!file_exists($path)) {
            throw new \RuntimeException("Config file not found: $path");
        }
        $config = include $path;
        if (!is_array($config)) {
            throw new \UnexpectedValueException("Config file must return an array: $path");
        }

        self::$config = $config;

        // Initialize database config if present
        if (isset($config['database'])) {
            self::$databaseConfig = new DatabaseConfig($config['database']);
        }

        return $config;
    }

    /**
     * Get database configuration
     *
     * @return DatabaseConfig|null
     */
    public static function getDatabaseConfig(): ?DatabaseConfig
    {
        if (self::$databaseConfig === null && !empty(self::$config['database'])) {
            self::$databaseConfig = new DatabaseConfig(self::$config['database']);
        }
        return self::$databaseConfig;
    }

    /**
     * Set database configuration
     *
     * @param DatabaseConfig $databaseConfig
     * @return void
     */
    public static function setDatabaseConfig(DatabaseConfig $databaseConfig): void
    {
        self::$databaseConfig = $databaseConfig;
        self::$config['database'] = $databaseConfig->getConfig();
    }

    /**
     * Get configuration value
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get(string $key, $default = null)
    {
        $keys = explode('.', $key);
        $value = self::$config;

        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return $default;
            }
            $value = $value[$k];
        }

        return $value;
    }

    /**
     * Set configuration value
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public static function set(string $key, $value): void
    {
        $keys = explode('.', $key);
        $config = &self::$config;

        foreach ($keys as $k) {
            if (!isset($config[$k])) {
                $config[$k] = [];
            }
            $config = &$config[$k];
        }

        $config = $value;
    }
}