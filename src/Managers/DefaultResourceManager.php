<?php
namespace Ramphor\Rake\Managers;

use Ramphor\Rake\Abstracts\ResourceManager;
use Ramphor\Rake\Abstracts\Tooth;
use Ramphor\Rake\Resource;
use Ramphor\Rake\Facades\DB;
use Ramphor\Rake\Facades\Instances;

class DefaultResourceManager extends ResourceManager
{
    public function createFromResult($result, $tooth = null): ResourceManager
    {
        if (is_null($tooth)) {
            $tooth = $result->getTooth();
        }
        $this->resources  = [];
        $rake             = $tooth->getRake();
        $resultResources  = $result->getResources();
        $parent           = Resource::create($result->getGuid(), 'link', $tooth);
        if ($result->isSuccess()) {
            $parent->imported();
            $parent->setNewGuid($result->getNewGuid());
            $parent->setNewType($result->getNewType());
            $parent->setContent($result->getContent(false));
        }

        foreach ($resultResources as $resultResource) {
            // Parse link to ensure working correctly
            $resultResource['guid']->parse();

            if ($resultResource['type'] === 'link' && !$resultResource['guid']->isSameSource()) {
                continue;
            }
            if (!in_array($resultResource['guid']->scheme, $this->protocols)) {
                continue;
            }

            $resource = Resource::create(
                (string)$resultResource['guid'],
                $resultResource['type'],
                $tooth
            );
            $resource->setParent($parent);

            // Make reference
            $this->resources[] = $resource;
        }

        // Freeup memory
        unset($resultResources);

        // Return current ResourceManager instance
        return $this;
    }

    protected function queryFileResources($rakeId, $toothId, $limit = 10)
    {
        $query = sql()->select('*')
            ->from(DB::table('rake_resources'))
            ->where(
                'rake_id =? AND tooth_id = ? AND imported=? AND skipped = ? AND resource_type <> ?',
                $rakeId,
                $toothId,
                0,
                0,
                'link'
            )->orderBy('retry ASC, updated_at ASC, created_at ASC, ID ASC')
            ->limit($limit);
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

    public function getFilesFromDatabase(Tooth $tooth): ResourceManager
    {
        $this->resources = [];
        $rake            = $tooth->getRake();
        $filesResources  = $this->queryFileResources(
            $rake->getId(),
            $tooth->getId(),
            $tooth->limitQueryResource()
        );

        foreach ($filesResources as $filesResource) {
            $tooth = $this->findTheTooth($filesResource->rake_id, $filesResource->tooth_id);
            if (is_null($tooth)) {
                // Will logging later
                continue;
            }

            $resource = Resource::create(
                $filesResource->guid,
                $filesResource->resource_type,
                $tooth
            );
            $resource = $this->mapFromDB($resource, $filesResource);

            array_push($this->resources, $resource);
        }

        // Return this instance after get resource from database
        return $this;
    }
}
