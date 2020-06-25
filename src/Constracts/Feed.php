<?php
namespace Ramphor\Rake\Constracts;

use Resource;
use Ramphor\Rake\DataSource\FeedIterator;

interface Feed
{
    public function fetch();

    // Convert feed data to Rake feed item
    public function convert($stream): FeedIterator;

    public function getItems(): FeedIterator;
}
