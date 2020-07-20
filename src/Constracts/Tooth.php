<?php
namespace Ramphor\Rake\Constracts;

use Iterator;
use Ramphor\Rake\Resource;
use Ramphor\Rake\Response;
use Ramphor\Rake\DataSource\FeedItem;

interface Tooth
{
    public function getResponse(): Response;

    public function getItems(): Iterator;

    public function downloadResource(Resource $resource): Resource;

    public function validateSystemResource($newGuid, $newType): bool;

    public function updatePostResource(Resource $resource);

    public function updateSystemResource(Resource $resource, Resource $parentResource);
}
