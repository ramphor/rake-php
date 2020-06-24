<?php
namespace Ramphor\Rake\Constracts;

use Ramphor\Rake\DataSource\FeedIterator;

interface Feed
{
    public function getItems(): FeedIterator;
}
