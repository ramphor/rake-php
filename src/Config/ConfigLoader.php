<?php

namespace Ramphor\Rake\Config;

/**
 * ConfigLoader: Load cấu hình từ file PHP trả về mảng, có thể mở rộng cho DB/env.
 */
class ConfigLoader
{
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
        return $config;
    }
}