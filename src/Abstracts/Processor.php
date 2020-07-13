<?php
namespace Ramphor\Rake\Abstracts;

use Monolog\Logger;
use Ramphor\Rake\Link;
use Ramphor\Rake\Constracts\Processor as ProcessorConstract;
use Ramphor\Rake\DataSource\FeedItem;
use Ramphor\Rake\Abstracts\Tooth;
use Ramphor\Rake\Facades\Resources;
use Ramphor\Rake\Facades\Document;

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
        $this->feedItem = $feedItem;
    }

    public function setToothParent(Tooth &$tooth)
    {
        $this->tooth = $tooth;
    }

    protected function getFeedImagesResources()
    {
        $resources = [];
        if ($this->feedId->coverImage) {
            array_push($resources, [
                'guid' => new Link($this->feedId->coverImage, $this->feedItem->guid),
                'type' => 'cover'
            ]);
        }
        if (is_array($this->feedItem->galleryImages) && count($this->feedItem->galleryImages) > 0) {
            foreach ($this->feedItem->galleryImages as $imageUrl) {
                array_push($resources, [
                    'guid' => new Link($imageUrl, $this->feedItem->guid),
                    'type' => 'gallary',
                ]);
            }
        }

        return $resources;
    }

    protected function getContentLinkResources()
    {
        $resources = [];
        if (empty($this->feedItem->body)) {
            return $resources;
        }

        $document = Document::load($this->feedItem->body);
        $links = $document->find('a');
        foreach ($links as $link) {
            array_push($resources, [
                'guid' => new Link($link->getAttribute('href'), $this->feedItem->guid),
                'type' => 'link',
            ]);
        }

        return $resources;
    }

    public function getResources()
    {
        $resources = $this->getFeedImagesResources();
        if ($this->crawlLinks) {
            $resources = array_merge_recursive($resources, $this->getContentLinkResources());
        }

        $rake = $this->tooth->getRake();
        foreach ($resources as $resource) {
            Resources::append(Resources::create(
                $resource['guid'],
                $resource['type'],
                $rake->getId(),
                $this->tooth->getId()
            ));
        }
    }
}
