<?php
namespace Ramphor\Rake\Constracts;

use Iterator;
use Ramphor\Rake\DataSource\FeedItem;

interface Parser extends Iterator
{
    public function current(): FeedItem;
}
