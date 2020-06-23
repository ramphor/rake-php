<?php
namespace Ramphor\Rake\Constracts;

use Ramphor\Rake\Data\FeedIterator;

interface Feed
{
    public function getItems(): FeedIterator;
}
