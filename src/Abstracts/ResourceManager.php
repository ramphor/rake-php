<?php

namespace Ramphor\Rake\Abstracts;

use Ramphor\Sql as QueryBuilder;
use Ramphor\Rake\Constracts\ResourceManager as ResourceManagerContract;
use Ramphor\Rake\Resource;
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

    protected function findQuery(QueryBuilder $query): ? Resource
    {
        $row = DB::row($query);
        if (empty($row)) {
            return null;
        }
        $rake = Intances::find($row->rake_id);
        if (is_null($rake)) {
            return null;
        }
        $tooth = $rake->findTheTooth($row->tooth_id);
        if (is_null($tooth)) {
            return null;
        }

        $resource = Resource::create($row->guid, $row->resource_type, $tooth);
        return $this->mapFromDB($resource, $row);
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

    public function getFromHash($hash): ? Resource
    {
        $query = sql()->select("s.*")->from(DB::table('rake_resources') . ' s')
            ->innerJoin(DB::table('rake_hash_map') . ' h')
            ->on('s.ID = h.resource_id')
            ->where('h.sha256 = ?', $hash);

        return $this->findQuery($query);
    }
}
