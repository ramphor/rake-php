<?php
namespace Ramphor\Rake\Abstracts;

use Monolog\Logger;
use Ramphor\Rake\Constracts\Processor as ProcessorConstract;
use Ramphor\Rake\DataSource\FeedItem;
use Ramphor\Rake\Abstracts\Tooth;

abstract class Processor implements ProcessorConstract
{
    protected $id;
    protected $tooth;
    protected $feedItem;

    protected $crawlLinks = false;

    public function setId(string $id)
    {
        $this->id = $id;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setFeedItem(FeedItem &$feedItem)
    {
        $this->feedItem =$feedItem;
    }

    public function setToothParent(Tooth &$tooth)
    {
        $this->tooth = $tooth;
    }

    protected function getFeedImagesResources()
    {
    }

    protected function getContentLinkResources()
    {
    }

    public function getResources()
    {
        $resources = $this->getFeedImagesResources();

        return $resources;
    }
}
