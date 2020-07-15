<?php
namespace Ramphor\Rake\Managers;

use Ramphor\Rake\Rake;

class InstanceManager
{
    protected $instances = [];

    public function add(Rake &$instance)
    {
        if (!isset($this->instances[$instance->getId()])) {
            $this->instances[$instance->getId()] = $instance;
        }
    }

    public function find($id)
    {
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }
    }
}
