<?php

namespace Ramphor\Rake\Abstracts;

use Ramphor\Rake\Constracts\ResourceManager as ResourceManagerContract;
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
            $tooth       = $resource->getTooth();
            $newResource = $tooth->downloadResource($resource);

            /* After download files via Tooth.
             * Rake will be update resource to database with new GUID and Type
             */
            $newResource->save();
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

        $resource->mapOthers([
            'init_hash' => $dbResource->init_hash,
            'retry' => $dbResource->retry,
            'created_at' => $dbResource->created_at,
            'updated_at' => $dbResource->updated_at,
        ]);

        return $resource;
    }
}
