<?php
namespace Ramphor\Rake;

use Ramphor\Rake\DataSource\FeedItem;
use Ramphor\Rake\Abstracts\Tooth;
use PHPHtmlParser\Dom as Document;

class ProcessResult
{
    protected $guid;
    protected $content;
    protected $resultType;

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

        return $result->setResultType('success');
    }

    public static function createErrorResult($errorMessage, $errorType = 'skip'): self
    {
        $result = new static(null);
        $result->addErrorMessage($errorMessage);

        return $result->setResultType($errorType);
    }

    public function getGuid()
    {
        return $this->guid;
    }

    public function setResultType($resultType): self
    {
        $this->resultType = $resultType;
        return $this;
    }

    public function isSuccess()
    {
        return $this->resultType === 'success';
    }

    public function isError()
    {
        return $this->resultType === 'error';
    }

    public function isSkipped()
    {
        return $this->resultType === 'skip';
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
        $this->content  = new Document();

        $this->content->load((string)$feedItem->content);
    }

    public function getFeedItem()
    {
        return $this->feedItem;
    }

    public function getContent($loadDom = false)
    {
        if ($loadDom) {
            return $this->content;
        }
        return $this->content->outerHtml;
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
                'guid' => Link::create($this->feedItem->coverImage, $this->feedItem->guid),
                'type' => 'cover_image'
            ]);
        }
        if (is_array($this->feedItem->galleryImages) && count($this->feedItem->galleryImages) > 0) {
            foreach ($this->feedItem->galleryImages as $imageUrl) {
                array_push($resources, [
                    'guid' => Link::create($imageUrl, $this->feedItem->guid),
                    'type' => 'gallary_image',
                ]);
            }
        }

        return $resources;
    }

    protected function getContentImageResources()
    {
        if (is_null($this->content)) {
            return [];
        }

        $resources = [];
        $images    = $this->content->find('img');
        foreach ($images as $image) {
            $image_url = Link::create($image->getAttribute('src'), $this->feedItem->guid);
            array_push($resources, [
                'guid' => $image_url,
                'type' => 'content_image',
            ]);
            $image->setAttribute('src', (string)$image_url);
        }

        return $resources;
    }

    protected function getContentLinkResources()
    {
        if (is_null($this->content)) {
            return [];
        }

        $resources = [];
        $links     = $this->content->find('a');
        foreach ($links as $link) {
            if (preg_match('/^#/', $link)) {
                continue;
            }

            $url = Link::create($link->getAttribute('href'), $this->feedItem->guid);
            array_push($resources, [
                'guid' => $url,
                'type' => 'link',
            ]);
            $link->setAttribute('href', (string)$url);
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
