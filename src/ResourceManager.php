<?php
namespace Ramphor\Rake;

use Ramphor\Rake\Abstracts\ResourceManager as ResourceManagerAbstract;

class ResourceManager extends ResourceManagerAbstract
{
    protected $resources = [];

    public function createResourcesFromResult($result): self
    {
        return $this;
    }

    public function import()
    {
    }
}
