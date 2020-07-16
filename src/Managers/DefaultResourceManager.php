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
            $resource->setId($filesResource->ID);
            if ((bool)$filesResource->imported) {
                $resource->imported();
            }
            $resource->setNewGuid($filesResource->new_guid);
            $resource->setNewType($filesResource->new_type);
            $resource->setContent($filesResource->content_text);

            $resource->mapOthers([
                'init_hash' => $filesResource->init_hash,
                'retry' => $filesResource->retry,
                'created_at' => $filesResource->created_at,
                'updated_at' => $filesResource->updated_at,
            ]);

            array_push($this->resources, $resource);
        }

        // Return this instance after get resource from database
        return $this;
    }
}
