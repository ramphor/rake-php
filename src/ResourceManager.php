<?php
namespace Ramphor\Rake;

use Ramphor\Rake\Resource;
use Ramphor\Rake\Abstracts\ResourceManager as ResourceManagerAbstract;

class ResourceManager extends ResourceManagerAbstract
{
    protected $resources = [];

    public function createFromResult($result): self
    {
        $tooth = $result->getTooth();
        $rake  = $tooth->getRake();

        $rawResources = $result->getResources();
        $resource = Resource::create($result->getGuid(), 'link', $rake->getId(), $tooth->getId());
        if ($result->isSuccess()) {
            $resource->imported();
            $resource->setNewGuid($result->getNewGuid());
            $resource->setNewType($result->getNewType());
            $resource->setContent($result->getContent(false));
        }
        // Insert processing URL to resource with flag `imported` is `true`
        array_push($this->resources, $resource);

        foreach ($rawResources as $rawResource) {
            if ($rawResource['type'] === 'link' && !$rawResource['guid']->isSameSource()) {
                continue;
            }

            $resource = Resource::create(
                (string)$rawResource['guid'],
                $rawResource['type'],
                $rake->getId(),
                $tooth->getId()
            );
            array_push($this->resources, $resource);
        }

        // Return current ResourceManager instance
        return $this;
    }

    public function import()
    {
        foreach ($this->resources as $resource) {
            $resource->save();
        }
    }
}
