<?php
namespace Ramphor\Rake\Managers;

use Ramphor\Rake\Abstracts\ResourceManager;
use Ramphor\Rake\Resource;
use Ramphor\Rake\Facades\DB;

class DefaultResourceManager extends ResourceManager
{
    public function createFromResult($result): ResourceManager
    {
        $tooth = $result->getTooth();
        $rake  = $tooth->getRake();

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

    protected function queryResources() {
        $query = sql()->select('*')
            ->from(DB::table('rake_resources'))
            ->where('imported=?', 0)
            ->orderBy('retry ASC, updated_at ASC, created_at ASC, ID ASC')
            ->limit(10);

        return DB::get($query);
    }

    public function getFromDatabase(): ResourceManager
    {
        $dbResources = $this->queryResources();

        return $this;
    }
}
