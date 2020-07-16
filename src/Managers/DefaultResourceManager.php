<?php
namespace Ramphor\Rake\Managers;

use Ramphor\Rake\Abstracts\ResourceManager;
use Ramphor\Rake\Resource;
use Ramphor\Rake\Facades\DB;
use Ramphor\Rake\Facades\Instances;

class DefaultResourceManager extends ResourceManager
{
    public function createFromResult($result): ResourceManager
    {
        $this->resources = [];
        $tooth           = $result->getTooth();
        $rake            = $tooth->getRake();

        $rawResources = $result->getResources();
        $parent =& Resource::create($result->getGuid(), 'link', $tooth);
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
                $tooth
            );
            $parent->addRelation($resource, 'child');
            array_push($this->resources, $resource);
        }

        // Freeup memory
        unset($rawResources);

        // Return current ResourceManager instance
        return $this;
    }

    protected function queryFileResources()
    {
        $query = sql()->select('*')
            ->from(DB::table('rake_resources'))
            ->where('imported=? AND resource_type <> ?', 0, 'link')
            ->orderBy('retry ASC, updated_at ASC, created_at ASC, ID ASC')
            ->limit(10);
        $rows = DB::get($query);

        return empty($rows) ? [] : $rows;
    }

    protected function findTheTooth($rakeId, $toothId)
    {
        $rake = Instances::find($rakeId);
        if (is_null($rake)) {
            // Will logging later
            return;
        }

        return $rake->findTooth($toothId);
    }

    public function getFilesFromDatabase(): ResourceManager
    {
        $this->resources = [];
        $filesResources  = $this->queryFileResources();

        foreach ($filesResources as $filesResource) {
            $tooth = $this->findTheTooth($filesResource->rake_id, $filesResource->tooth_id);
            if (is_null($tooth)) {
                // Will logging later
                continue;
            }

            $resource =& Resource::create(
                $filesResource->guid,
                $filesResource->resource_type,
                $tooth
            );
            $this->mapFromDB($resource, $filesResource);

            array_push($this->resources, $resource);
        }

        // Return this instance after get resource from database
        return $this;
    }

    public function find(int $resouceId): ? Resource
    {
        $query = sql()->select("*")
            ->from(DB::table('rake_resources'))
            ->where('ID=?', $resourceId);

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

        $resource =& Resource::create($row->guid, $row->resource_type, $tooth);
        return $this->mapFromDB($resource, $row);
    }
}
