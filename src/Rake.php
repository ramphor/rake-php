<?php

namespace Rake;

use Illuminate\Container\Container;
use Rake\Manager\DatabaseDriverManager;

/**
 * Entrypoint cho core framework Rake 2.0
 * Khởi tạo managers, event bus, cấu hình, ...
 */
class Rake extends Container
{
    /**
     * @var Rake|null
     */
    protected static $instance = null;

    /**
     * Khởi tạo managers, event bus, cấu hình, ...
     */
    private function __construct()
    {
        Bootstrapper::register($this);
    }

    /**
     * Lấy instance duy nhất của Rake
     *
     * @return Rake
     */
    public static function getInstance()
    {
        if (static::$instance === null) {
            static::$instance = (new static())->make(Rake::class);
        }
        return static::$instance;
    }

    /**
     * Lấy DatabaseDriverManager
     *
     * @return DatabaseDriverManager
     */
    public function getDatabaseDriverManager(): DatabaseDriverManager
    {
        return $this->make(DatabaseDriverManager::class);
    }

    /**
     * Register a service provider
     *
     * @param object|string $provider
     * @return void
     */
    public function register($provider): void
    {
        if (is_string($provider)) {
            $provider = new $provider();
        }
        if (method_exists($provider, 'register')) {
            $provider->register($this);
        }
        if (method_exists($provider, 'boot')) {
            $provider->boot($this);
        }
    }

    // Bạn có thể thêm các method mở rộng ở đây
}