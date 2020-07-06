<?php
namespace Ramphor\Rake\Constracts;

use Iterator;
use Ramphor\Rake\DataSource\FeedItem;
use Ramphor\Rake\DataSource\FeedItemBuilder;
use Ramphor\Rake\Abstracts\Parser as ParserAbstract;

interface Parser extends Iterator
{
    public function current(): FeedItem;

    public function setFeedItemBuilder(FeedItemBuilder $feedBuilder): ParserAbstract;
}
