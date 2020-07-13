<?php
namespace Ramphor\Rake\Constracts;

use Iterator;
use Resource;
use Ramphor\Rake\Response;
use Ramphor\Rake\DataSource\FeedItem;

interface Tooth
{
    public function fetch(): Response;

    public function getItems(): Iterator;
}
