<?php
namespace Ramphor\Rake;

use Iterator;

class App implements Iterator
{
    protected $resolvedInstance;
    protected $currentKey;

    protected static $instance;

    public static function instance()
    {
        if (is_null(static::$instance)) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    public function bind($name, $closure)
    {
        $this->resolvedInstance[$name] = $closure;
    }

    public function key()
    {
        $instanceName = array_keys($this->resolvedInstance);
        if (isset($instanceName[$this->currentKey])) {
            return $instanceName[$this->currentKey];
        }

        return $this->currentKey;
    }

    public function valid()
    {
        $instanceName = array_keys($this->resolvedInstance);

        return isset($instanceName[$this->currentKey]);
    }

    public function next()
    {
        $this->currentKey += 1;
    }

    public function current()
    {
        return $this->resolvedInstance[$this->key()];
    }

    public function rewind()
    {
        $this->currentKey = 0;
    }

    public function resolve($name)
    {
        if (isset($this->resolvedInstance[$name])) {
            return $this->resolvedInstance[$name];
        }
    }
}
