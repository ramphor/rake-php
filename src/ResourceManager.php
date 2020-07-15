<?php
namespace Ramphor\Rake;

use Ramphor\Rake\Abstracts\ResourceManager as ResourceManagerAbstract;
use Ramphor\Rake\Resource;
use Ramphor\Rake\Facades\DB;

class ResourceManager extends ResourceManagerAbstract
{
    protected $protocols = ['ftp', 'http', 'https'];
    protected $resources = [];

    public function createFromResult($result): ResourceManagerAbstract
    {
        $tooth = $result->getTooth();
        $rake  = $tooth->getRake();

        $rawResources = $result->getResources();
        $parent =& Resource::create($result->getGuid(), 'link', $rake->getId(), $tooth->getId());
        if ($result->isSuccess()) {
            $parent->imported();
            $parent->setNewGuid($result->getNewGuid());
            $parent->setNewType($result->getNewType());
            $parent->setContent($result->getContent(false));
        }
        // Insert processing URL to resource with flag `imported` is `true`
        array_push($this->resources, $parent);

        foreach ($rawResources as $rawResource) {
            if ($rawResource['type'] === 'link' && !$rawResource['guid']->isSameSource()) {
                continue;
            }
            if (!in_array($rawResource['guid']->scheme, $this->protocols)) {
                continue;
            }

            $resource =& Resource::create(
                (string)$rawResource['guid'],
                $rawResource['type'],
                $rake->getId(),
                $tooth->getId()
            );
            $parent->addRelation($resource, 'child');
            array_push($this->resources, $resource);
        }

        // Freeup memory
        unset($rawResources);

        // Return current ResourceManager instance
        return $this;
    }

    public function import()
    {
        foreach ($this->resources as $resource) {
            $resource->save();
            $relations = $resource->getRelations();
            if (count($relations) > 0) {
                $this->importRelations($relations, $resource);
            }
        }
    }

    protected function createRelation($resourceId, $sourceId, $type)
    {
        $query = sql()
            ->insertInto(DB::table('rake_relations'), ['resource_id', 'source_id', 'map_type'])
            ->values('?, ?, ?', $resourceId, $sourceId, $type);

        return DB::insert($query);
    }

    protected function importRelations($relations, $parent)
    {
        $parentId = $parent->findId();
        foreach ($relations as $type => $resources) {
            foreach ($resources as $resource) {
                $resourceId = $resource->findId();
                if ($resourceId <= 0) {
                    $resourceId = $resource->save();
                }

                $this->createRelation($resourceId, $parentId, $type);
            }
        }
    }
}
