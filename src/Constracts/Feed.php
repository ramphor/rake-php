<?php
namespace Ramphor\Rake\Constracts;

use Ramphor\Rake\DataSource\FeedIterator;

interface Feed
{
    // Convert feed data to Rake feed item
    public function convert();

    public function getItems(): FeedIterator;
}
