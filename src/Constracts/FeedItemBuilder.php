<?php

namespace Ramphor\Rake\Constracts;

use Ramphor\Rake\DataSource\FeedItem;

interface FeedItemBuilder
{
    public function setMappingFields($mappingFields);

    public function newItem($data);

    public function build();

    public function getFeedItem(): FeedItem;
}
