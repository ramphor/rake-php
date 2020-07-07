<?php
namespace Ramphor\Rake\Abstracts;

use Monolog\Logger;
use Ramphor\Rake\Constracts\Processor as ProcessorConstract;
use Ramphor\Rake\DataSource\FeedItem;

abstract class Processor extends TemplateMethod implements ProcessorConstract
{
    protected $feedItem;

    public function setFeedItem(FeedItem &$feedItem)
    {
        $this->feedItem =$feedItem;
    }

    public function validateFeedItem(): bool
    {
        return true;
    }
}
