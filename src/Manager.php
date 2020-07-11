<?php
namespace Ramphor\Rake;

use Ramphor\Rake\Rake;

final class Manager
{
    /**
     * The Rake application instance being facaded.
     *
     * @var \Ramphor\Rake\Rake
     */
    protected static $rake;

    protected static $instance;

    protected $connections = [];
    protected $defaultConnection;

    protected $httpClient;
    protected $httpClientInstance;

    public static function instance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // Make constructor is private method
    private function __construct()
    {
    }

    public static function __callStatic($name, $args)
    {
        $instance = static::instance();
        $callback = [$instance, $name];

        if (is_callable($callback)) {
            return call_user_func_array($callback, $args);
        }
    }

    /**
     * Set the application instance
     *
     * @param \Ramphor\Rake\Rake $rake
     * @return void
     */
    public static function setRakeApplication(Rake $rake)
    {
        static::$rake = $rake;
    }

    public function addConnection($name, $connection)
    {
        if (isset($this->connections[$name])) {
            return;
        }

        if (is_object($connection)) {
            $this->connections[$name] = $connection;
        }

        if (is_null($defaultConnection)) {
            $defaultConnection = $name;
        }
    }

    public function connection($name = null)
    {
        if (is_null($name)) {
            $name = $this->defaultConnection;
        }
        return $this->connections[$name];
    }

    public function registerHttpClient($httpClient)
    {
        if (is_object($httpClient)) {
            $this->httpClient = get_class($httpClient);
            $this->httpClientInstance = $httpClient;
        } else {
            $this->httpClient = $httpClient;
        }
    }

    public function getHttpClient()
    {
        return $this->httpClient;
    }

    public function httpClientInstance()
    {
        return $this->httpClientInstance;
    }
}
