<?php

namespace Ramphor\Rake\Managers;

use Http\Client\Exception;
use Ramphor\Rake\Abstracts\ResourceManager;
use Ramphor\Rake\Abstracts\Tooth;
use Ramphor\Rake\Link;
use Ramphor\Rake\Resource;
use Ramphor\Rake\Facades\DB;
use Ramphor\Rake\Facades\Request;
use Ramphor\Rake\Facades\Logger;
use Ramphor\Rake\Facades\Instances;
use Ramphor\Rake\Facades\Option;
use Ramphor\Rake\Facades\Resources;

class DefaultResourceManager extends ResourceManager
{
    protected function checkLinkResourceIsOk($resource)
    {
        return $resource['guid']->isSameSource();
    }

    public function createFromResult($result, $tooth = null): ResourceManager
    {
        if (is_null($tooth)) {
            $tooth = $result->getTooth();
        }
        $this->resources  = [];
        $resultResources  = $result->getResources();
        $parent           = Resource::create($result->getGuid(), 'link', $tooth);
        $parent->setContent($result->getContent(false));
        if ($result->isSuccess()) {
            $parent->imported();
            $parent->setNewGuid($result->getNewGuid());
            $parent->setNewType($result->getNewType());
        }

        foreach ($resultResources as $resultResource) {
            if (!$resultResource['guid'] instanceof Link) {
                Logger::info(sprintf('The %s resource has guid is not instance of %s', $resultResource['guid'], Link::class));
                continue;
            }

            // Parse link to ensure working correctly
            $resultResource['guid']->parse();
            Logger::info(sprintf(
                'Create a %s resource from result with GUID is "%s"',
                $resultResource['type'],
                $resultResource['guid']->__toString(),
            ));

            if ($resultResource['type'] === 'link' && !$this->checkLinkResourceIsOk($resultResource)) {
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
            )->orderBy('retry ASC, updated_at ASC, created_at ASC, id ASC')
            ->limit($limit);
        $rows = DB::get($query);

        return empty($rows) ? [] : $rows;
    }

    protected function findTheTooth($rakeId, $toothId)
    {
        $rake = Instances::find($rakeId);
        if (is_null($rake)) {
            Logger::warning('The tooth is doesn\'t have a Rake instance to processing', [
                'tooth_id' => $toothId,
                'rake_id'  => $rakeId,
            ]);
            return;
        }

        return $rake->findTooth($toothId);
    }

    public function getFilesFromDatabase(Tooth $tooth): ResourceManager
    {
        $this->resources = [];
        $rake            = $tooth->getRake();
        $fileResources  = $this->queryFileResources(
            $rake->getId(),
            $tooth->getId(),
            $tooth->getLimitQueryResources()
        );

        $totalResources = count($fileResources);

        $notifiedKey = sprintf('tooth_%s_resources_notified', $tooth->getId());
        $notified    = Option::get($notifiedKey, false);
        if ($totalResources <= 0) {
            if (!$notified) {
                Logger::alert(sprintf(
                    'Not found any resource of the  %s tooth. It means the process resource maybe completed.',
                    $tooth->getId()
                ));
                Option::update($notifiedKey, true);
            }

            return $this;
        }

        foreach ($fileResources as $fileResource) {
            $tooth = $this->findTheTooth($fileResource->rake_id, $fileResource->tooth_id);
            if (is_null($tooth)) {
                Logger::alert('The resource doesn\'t have a tooth continue processing', [
                    'id'       => $fileResource->id,
                    'type'     => $fileResource->resource_type,
                    'tooth_id' => $fileResource->tooth_id,
                ]);
                continue;
            }

            $fileResourceId = $fileResource->id;
            $parentResource = Resources::findParent($fileResourceId);
            if (!empty($parentResource)) {
                $parentResourceId = $parentResource->id;
            } else {
                $parentResourceId = null;
            }

            Logger::info(sprintf('Create a resource from database %s', var_export([
                'id'   => $fileResourceId,
                'type' => $fileResource->resource_type,
                'parent' => $parentResourceId
            ], true)));

            $resource = Resource::create(
                $fileResource->guid,
                $fileResource->resource_type,
                $tooth
            );
            $resource = $this->mapFromDB($resource, $fileResource);

            if ($parentResourceId > 0) {
                $resource->setParent($parentResource);
            }

            array_push($this->resources, $resource);
        }

        // Return this instance after get resource from database
        return $this;
    }
}
