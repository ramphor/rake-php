<?php
namespace Ramphor\Rake;

use Ramphor\Rake\DataSource\FeedItem;
use Ramphor\Rake\Abstracts\Tooth;

class ProcessResult
{
    protected $guid;
    protected $resultType;
    protected $isSkipped;

    protected $newGuid;
    protected $newType;
    protected $feedItem;
    protected $tooth;

    protected $errors = [];

    public function __construct($guid)
    {
        $this->guid = $guid;
    }

    public static function createSuccessResult($guid, $newGuid, $newType): self
    {
        $result = new static($guid);

        $result->setNewGuid($newGuid);
        $result->setNewType($newType);

        return $result->setResultType(true);
    }

    public static function createErrorResult($errorMessage, $isSkipped = false): self
    {
        $result = new static($guid);

        $result->skip($isSkipped);
        $result->addErrorMessage($errorMessage);

        return $result->setResultType(false);
    }

    public function getGuid()
    {
        return $this->guid;
    }

    public function skip($isSkipped = false)
    {
        $this->isSkipped = (bool) $isSkipped;
    }

    public function isSkipped()
    {
        return $this->isSkipped;
    }

    public function setResultType(bool $isSuccess): self
    {
        $this->resultType = $isSuccess;
        return $this;
    }

    public function isSuccess()
    {
        return $this->resultType;
    }

    public function setNewGuid($newGuid)
    {
        $this->newGuid = $newGuid;
    }

    public function getNewGuid()
    {
        return $this->newGuid;
    }

    public function setNewType($newType)
    {
        $this->newType = $newType;
    }

    public function getNewType()
    {
        return $this->newType;
    }

    public function setFeedItem(FeedItem &$feedItem)
    {
        $this->feedItem = $feedItem;
    }

    public function getFeedItem()
    {
        return $this->feedItem;
    }

    public function setProcessingTooth(Tooth &$tooth)
    {
        $this->tooth = $tooth;
    }

    public function getTooth()
    {
        return $this->tooth;
    }

    public function addErrorMessage($errorMessage)
    {
        array_push($this->errors, $errorMessage);
    }

    // Get first error message
    public function getErrorMessage()
    {
        if (count($this->errors) <= 0) {
            return '';
        }
        return $this->errors[0];
    }

    public function getErrorMessages()
    {
        return $this->errors;
    }

    protected function getFeedImagesResources()
    {
        $resources = [];
        if ($this->feedItem->coverImage) {
            array_push($resources, [
                'guid' => new Link($this->feedId->coverImage, $this->feedItem->guid),
                'type' => 'cover'
            ]);
        }
        if (is_array($this->feedItem->galleryImages) && count($this->feedItem->galleryImages) > 0) {
            foreach ($this->feedItem->galleryImages as $imageUrl) {
                array_push($resources, [
                    'guid' => Link::create($imageUrl, $this->feedItem->guid),
                    'type' => 'gallary',
                ]);
            }
        }

        return $resources;
    }

    protected function getContentImageResources()
    {
        $resources = [];
        if (empty($this->feedItem->body)) {
            return $resources;
        }

        $document = Document::load($this->feedItem->body);
        $images    = $document->find('img');
        foreach ($images as $image) {
            array_push($resources, [
                'guid' => Link::create($image->getAttribute('src'), $this->feedItem->guid),
                'type' => 'content_image',
            ]);
        }

        return $resources;
    }

    protected function getContentLinkResources()
    {
        $resources = [];
        if (!is_null($this->feedItem) && !$this->feedItem->body) {
            return $resources;
        }

        $document = Document::load($this->feedItem->body);
        $links    = $document->find('a');
        foreach ($links as $link) {
            array_push($resources, [
                'guid' => Link::create($link->getAttribute('href'), $this->feedItem->guid),
                'type' => 'link',
            ]);
        }

        return $resources;
    }

    public function getResources()
    {
        return array_merge(
            $this->getFeedImagesResources(),
            $this->getContentImageResources(),
            $this->getContentLinkResources(),
        );
    }
}
