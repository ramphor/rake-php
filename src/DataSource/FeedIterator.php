<?php
namespace Ramphor\Rake\DataSource;

use Iterator;

class FeedIterator implements Iterator
{
    public function key()
    {
    }

    public function current():FeedItem
    {
        return new FeedItem();
    }

    public function next():bool
    {
        return true;
    }

    public function valid():bool
    {
        return true;
    }

    public function rewind():void
    {
    }
}
