<?php
namespace Ramphor\Rake;

use Ramphor\Rake\Abstracts\ResourceManager as ResourceManagerAbstract;

class ResourceManager extends ResourceManagerAbstract
{
    protected $resources = [];

    public function createFromResult($result): self
    {
        return $this;
    }

    public function import()
    {
    }
}
