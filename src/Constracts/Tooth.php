<?php
namespace Ramphor\Rake\Constracts;

use Ramphor\Rake\Abstracts\Feed;
use Ramphor\Rake\Resource;
use Ramphor\Rake\Response;
use Ramphor\Rake\DataSource\FeedItem;
use Ramphor\Rake\Rake;

interface Tooth
{
    public function setRake(Rake $rake);

    public function getResponses();

    public function getParsers();

    public function addMappingField($fieldName, $mappingRules);

    public function addFeed(Feed $feed);

    public function downloadResource(Resource &$resource): Resource;

    public function validateSystemResource($newGuid, $newType): bool;

    public function updatePostResource(Resource $resource);

    public function updateSystemResource(Resource $resource, Resource $parentResource);

    public function isCrawlUrlInContent();
}
