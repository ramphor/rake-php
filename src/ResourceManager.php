<?php
namespace Ramphor\Rake;

use Ramphor\Rake\Abstracts\ResourceManager as ResourceManagerAbstract;

class ResourceManager extends ResourceManagerAbstract
{
    protected $resources = [];

    public function append(Resource $resource)
    {
        array_push($this->resources, $resource);
    }

    public function create($guid, $type, $rakeId, $toothId)
    {
        return new Resource((string)$guid, $type, $rakeId, $toothId);
    }

    public function get()
    {
        return array_values($this->resources);
    }
}
