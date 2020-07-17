<?php

namespace Ramphor\Rake\Abstracts;

use Ramphor\Sql as QueryBuilder;
use Ramphor\Rake\Constracts\ResourceManager as ResourceManagerContract;
use Ramphor\Rake\Resource;
use Ramphor\Rake\Facades\Instances;
use Ramphor\Rake\Facades\DB;
use Ramphor\Rake\Facades\Crawler;

abstract class ResourceManager implements ResourceManagerContract
{
    protected $protocols = ['ftp', 'http', 'https'];
    protected $resources = [];

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

    protected function relationIsExists($resourceId, $sourceId, $type)
    {
        $query = sql()->select("COUNT(resource_id)")
            ->from(DB::table('rake_relations'))
            ->where('resource_id=? AND source_id=? AND map_type=?', $resourceId, $sourceId, $type);

        return (int)DB::var($query) > 0;
    }

    protected function createRelation($resourceId, $sourceId, $type)
    {
        if ($this->relationIsExists($resourceId, $sourceId, $type)) {
            return;
        }

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
            $resource =& $tooth->downloadResource($resource);

            /* After download files via Tooth.
             * Rake will be update resource to database with new GUID and Type
             */
            $resource->save();
        }
    }

    protected function mapFromDB(Resource $resource, $dbResource)
    {
        $resource->setId($dbResource->ID);
        if ((bool)$dbResource->imported) {
            $resource->imported();
        }
        $resource->setNewGuid($dbResource->new_guid);
        $resource->setNewType($dbResource->new_type);
        $resource->setContent($dbResource->content_text);

        $resource->mapOthers([
            'init_hash' => $dbResource->init_hash,
            'retry' => $dbResource->retry,
            'created_at' => $dbResource->created_at,
            'updated_at' => $dbResource->updated_at,
        ]);

        return $resource;
    }

    protected function findQuery(QueryBuilder $query, callable $callback = null): ? Resource
    {
        $queryResult = DB::row($query);
        if (empty($queryResult)) {
            return null;
        }
        $rake = Instances::find($queryResult->rake_id);
        if (is_null($rake)) {
            return null;
        }
        $tooth = $rake->findTooth($queryResult->tooth_id);
        if (is_null($tooth)) {
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

        return $this->findQuery($query);
    }

    public function generateHash($data, $type)
    {
        if (in_array($type, ['content_image', 'gallary', 'cover'])) {
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
            ->where('h.sha256 = ?, h.new_type=?', $hash, $type);

        return $this->findQuery($query, function ($resource, $queryResult) {
            if (is_null($resource)) {
                return $resource;
            }

            if (empty($resource->newGuid)) {
                $resource->newGuid = $queryResult->map_guid;
            }
            if (empty($resource->newType)) {
                $resource->newType = $queryResult->map_type;
            }

            return $resource;
        });
    }
}
