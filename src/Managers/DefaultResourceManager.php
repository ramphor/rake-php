<?php
namespace Ramphor\Rake\Managers;

use Ramphor\Rake\Abstracts\ResourceManager;
use Ramphor\Rake\Abstracts\Tooth;
use Ramphor\Rake\Link;
use Ramphor\Rake\Resource;
use Ramphor\Rake\Facades\DB;
use Ramphor\Rake\Facades\Client;
use Ramphor\Rake\Facades\Logger;
use Ramphor\Rake\Facades\Instances;

class DefaultResourceManager extends ResourceManager
{
    protected function checkLinkResourceIsOk($resource)
    {
        if (!$resource['guid']->isSameSource()) {
            return false;
        }
        $response = Client::request('HEAD', (string)$resource['guid']);
        $mimeType = $response->getHeaderLine('Content-Type');

        return preg_match('/^(text|application)\//', $mimeType);
    }

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
            if (!$resultResource['guid'] instanceof Link) {
                Logger::info(sprintf('The %s resource has guid is not instance of %s', Link::class));
                continue;
            }

            // Parse link to ensure working correctly
            $resultResource['guid']->parse();
            Logger::debug(sprintf(
                'Create resources from result %s',
                $resultResource['type'],
                $resultResource['guid']->__toString()
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
            )->orderBy('retry ASC, updated_at ASC, created_at ASC, ID ASC')
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
            $tooth->limitQueryResource()
        );

        foreach ($fileResources as $fileResource) {
            $tooth = $this->findTheTooth($fileResource->rake_id, $fileResource->tooth_id);
            if (is_null($tooth)) {
                Logger::warning('The resource doesn\'t have a tooth continue processing', [
                    'ID'       => $fileResource->id,
                    'type'     => $fileResource->resource_type,
                    'tooth_id' => $fileResource->tooth_id,
                ]);
                continue;
            }

            Logger::debug(sprintf('Create a resource from database %s', var_export([
                'ID'   => $fileResource->id,
                'type' => $fileResource->resource_type,
            ], true)));
            $resource = Resource::create(
                $fileResource->guid,
                $fileResource->resource_type,
                $tooth
            );
            $resource = $this->mapFromDB($resource, $fileResource);

            array_push($this->resources, $resource);
        }

        // Return this instance after get resource from database
        return $this;
    }
}
