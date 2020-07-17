<?php
namespace Ramphor\Rake\Constracts;

use Iterator;
use Ramphor\Rake\Resource;
use Ramphor\Rake\Response;
use Ramphor\Rake\DataSource\FeedItem;

interface Tooth
{
    public function fetch(): Response;

    public function getItems(): Iterator;

    public function downloadResource(Resource $resource): Resource;

    public function validateSystemResource($newGuid, $newType): bool;

    public function updateParentSystemResource(Resource $resource, Resource $parentResource);

    public function updateChildSystemResource(Resource $resource, $childrens);
}
