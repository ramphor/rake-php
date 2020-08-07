<?php

namespace Ramphor\Rake\Abstracts;

use Ramphor\Sql as QueryBuilder;
use Ramphor\Rake\Constracts\ResourceManager as ResourceManagerContract;
use Ramphor\Rake\Resource;
use Ramphor\Rake\Facades\Crawler;
use Ramphor\Rake\Facades\DB;
use Ramphor\Rake\Facades\Logger;
use Ramphor\Rake\Facades\Instances;

abstract class ResourceManager implements ResourceManagerContract
{
    protected $protocols = ['ftp', 'http', 'https'];
    protected $resources = [];

    public function import($onlyCreate = false)
    {
        foreach ($this->resources as $resource) {
            $parentId   = 0;
            $parent     = $resource->parent;
            if ($parent) {
                $parentId = $parent->findId();
                if ($parentId <= 0) {
                    $parentId = $parent->save();
                }
            }

            $resourceId = $resource->save($onlyCreate);
            if ($parentId > 0) {
                $this->createRelation($resourceId, $parentId);
            }
        }
    }

    protected function relationIsExists($resourceId, $sourceId)
    {
        $query = sql()->select("COUNT(resource_id)")
            ->from(DB::table('rake_relations'))
            ->where('resource_id=? AND parent_id=?', $resourceId, $sourceId);

        return (int)DB::var($query) > 0;
    }

    protected function createRelation($resourceId, $sourceId)
    {
        if ($this->relationIsExists($resourceId, $sourceId)) {
            return;
        }

        $query = sql()
            ->insertInto(DB::table('rake_relations'), ['resource_id', 'parent_id'])
            ->values('?, ?', $resourceId, $sourceId);

        return DB::insert($query);
    }

    public function importCrawlUrls()
    {
        foreach ($this->resources as $resource) {
            if ($resource->type !== 'link') {
                continue;
            }

            Crawler::importFromResource($resource);
        }
    }

    public function transferFiles()
    {
        if (empty($this->resources)) {
            return;
        }
        foreach ($this->resources as $resource) {
            $tooth    = $resource->getTooth();
            $resource = $tooth->downloadResource($resource);

            /* After download files via Tooth.
            * Rake will be update resource to database with new GUID and Type
            */
            $resource->save();
        }
    }

    protected function mapFromDB(Resource &$resource, $dbResource)
    {
        $resource->setId($dbResource->ID);
        if ((bool)$dbResource->imported) {
            $resource->imported();
        }
        $resource->setNewGuid($dbResource->new_guid);
        $resource->setNewType($dbResource->new_type);
        $resource->setContent($dbResource->content_text);
        $resource->setRetry($dbResource->retry);

        $resource->mapOthers([
            'init_hash' => $dbResource->init_hash,
            'retry' => $dbResource->retry,
            'created_at' => $dbResource->created_at,
            'updated_at' => $dbResource->updated_at,
        ]);

        return $resource;
    }

    public function findByQuery(QueryBuilder $query, callable $callback = null): ? Resource
    {
        $queryResult = DB::row($query);
        if (empty($queryResult)) {
            return null;
        }
        $rake = Instances::find($queryResult->rake_id);
        if (is_null($rake)) {
            return null;
        }

        Logger::debug(sprintf('Find the tooth from ID "%s"', $queryResult->tooth_id));
        $tooth = $rake->findTooth($queryResult->tooth_id);
        if (is_null($tooth)) {
            Logger::warning(sprintf('The tooth has ID %s is not found', $queryResult->tooth_id));
            return null;
        }

        $resource = Resource::create($queryResult->guid, $queryResult->resource_type, $tooth);
        $resource = $this->mapFromDB($resource, $queryResult);
        if (is_null($callback)) {
            return $resource;
        }

        return $callback($resource, $queryResult);
    }

    public function find(int $resouceId): ? Resource
    {
        $query = sql()->select("*")
            ->from(DB::table('rake_resources'))
            ->where('ID=?', $resourceId);

        return $this->findByQuery($query);
    }

    public function generateHash($data, $type)
    {
        if (in_array($type, ['content_image', 'gallary_image', 'cover_image'])) {
            return hash_file('sha256', $data);
        }
        return hash('sha256', $data);
    }

    public function getFromHash($hash, $type): ? Resource
    {
        $query = sql()->select("s.*, h.new_guid as map_guid, h.new_type as map_type")
            ->from(DB::table('rake_resources') . ' s')
            ->innerJoin(DB::table('rake_hash_maps') . ' h')
            ->on('s.ID = h.resource_id')
            ->where('h.sha256 = ? AND h.new_type=?', $hash, $type);

        return $this->findByQuery($query, function ($resource, $queryResult) {
            if (is_null($resource)) {
                return $resource;
            }

            if (empty($resource->newGuid)) {
                $resource->setNewGuid($queryResult->map_guid);
            }
            if (empty($resource->newType)) {
                $resource->setNewType($queryResult->map_type);
            }

            return $resource;
        });
    }

    public function findParent($childId)
    {
        $query = sql()->select("res.*")->from(DB::table('rake_resources res'))
            ->innerJoin(DB::table('rake_relations rel'))
            ->on('res.ID = rel.parent_id')
            ->where('rel.resource_id=?', $childId);

        $parent = $this->findByQuery($query);
        Logger::debug(sprintf('Find the parent resource from child #%s', $childId), (array)$parent);
        
        return $parent;
    }

    public function getTotalResources()
    {
        return count($this->resources);
    }
}
