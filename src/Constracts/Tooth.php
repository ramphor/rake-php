<?php
namespace Ramphor\Rake\Constracts;

use Iterator;
use Resource;
use Ramphor\Rake\DataSource\FeedItem;

interface Tooth
{
    public function fetch();

    public function getItems(): Iterator;

    public function createProcessor(FeedItem $feedItem);
}
