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

    public function isTransferResources();

    public function downloadResource(Resource &$resource): Resource;

    public function validateSystemResource($newGuid, $newType): bool;

    public function updatePostResource(Resource $resource);

    public function updateSystemResource(Resource $resource, Resource $parentResource);

    public function isCrawlUrlInContent();


    /**
     * @param callable|\Closure $callable
     */
    public function setFeedItemsEmptyAction($callable);

     /**
     * @return callable|\Closure
     */
    public function getFeedItemsEmptyAction();

    /**
     * @return array
     */
    public function setFeedItemsEmptyActionArgs($args);

    /**
     * @return array
     */
    public function getFeedItemsEmptyActionArgs();

    /**
     * @param callable $callable
     */
    public function setDuplicateAction($callable);

    /**
     * @return callable|null
     */
    public function getDuplicateAction();


    /**
     * @param array $callable
     */
    public function setDuplicateActionArgs($args);

    /**
     * @return array
     */
    public function getDuplicateActionArgs();

    public function collect();

    public function registerFeedItemCallbacks();
}
