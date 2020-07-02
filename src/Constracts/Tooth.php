<?php
namespace Ramphor\Rake\Constracts;

use Iterator;
use Resource;
use Ramphor\Rake\Http\Response;
use Ramphor\Rake\DataSource\FeedItem;

interface Tooth
{
    public function fetch(): Response;

    public function getItems(): Iterator;

    public function setMappingFields($mappingFields);
}
