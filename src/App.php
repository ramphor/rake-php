<?php

namespace Ramphor\Rake;

use Iterator;
use Ramphor\Rake\Managers\DefaultResourceManager;

class App implements Iterator
{
    /** @var array */
    protected $resolvedInstance = [];

    /** @var int|null */
    protected $currentKey;

    /** @var self|null */
    protected static $instance;

    /**
     * Get singleton instance
     * @return self
     */
    public static function instance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Bind instance to container
     * @param string $name
     * @param mixed $closure
     */
    public function bind($name, $closure)
    {
        if (!isset($this->resolvedInstance[$name])) {
            $this->resolvedInstance[$name] = $closure;
        }
    }

    /**
     * @return string|int|null
     */
    public function key()
    {
        $instanceName = array_keys($this->resolvedInstance);
        if (isset($instanceName[$this->currentKey])) {
            return $instanceName[$this->currentKey];
        }
        return $this->currentKey;
    }

    /**
     * @return bool
     */
    public function valid(): bool
    {
        return isset($this->resolvedInstance[$this->key()]);
    }

    /**
     * @return void
     */
    public function next(): void
    {
        $this->currentKey += 1;
    }

    /**
     * @return mixed
     */
    public function current()
    {
        return $this->resolvedInstance[$this->key()];
    }

    /**
     * @return void
     */
    public function rewind()
    {
        $this->currentKey = 0;
    }

    /**
     * Resolve instance from container
     * @param string $name
     * @return mixed
     */
    public function resolve($name)
    {
        if (isset($this->resolvedInstance[$name])) {
            if (is_callable($this->resolvedInstance[$name])) {
                return call_user_func($this->resolvedInstance[$name]);
            }
            return $this->resolvedInstance[$name];
        }

        if ($name === "resources") {
            $this->resolvedInstance[$name] = function () {
                return new DefaultResourceManager();
            };
            return $this->resolve($name);
        }

        return $this->resolve($name);
    }
}
