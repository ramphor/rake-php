<?php
namespace Ramphor\Rake;

use \Throwable;
use Ramphor\Rake\DataSource\FeedItem;
use Ramphor\Rake\Abstracts\Tooth;
use PHPHtmlParser\Dom as Document;
use Ramphor\Rake\Facades\Logger;

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

    protected static $contentImageCallbacks = [];

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

        $this->content->loadStr((string)$feedItem->content);
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
        $processor = $this->tooth->getProcessor();
        if ($this->feedItem->coverImage) {
            $convertedUrl = $processor->convertImageUrl($this->feedItem->coverImage);
            array_push($resources, [
                'guid' => Link::create($convertedUrl, $this->feedItem->guid),
                'type' => 'cover_image'
            ]);
        }
        if (is_array($this->feedItem->galleryImages) && count($this->feedItem->galleryImages) > 0) {
            foreach ($this->feedItem->galleryImages as $imageUrl) {
                $convertedUrl = $processor->convertImageUrl($imageUrl);
                array_push($resources, [
                    'guid' => Link::create($convertedUrl, $this->feedItem->guid),
                    'type' => 'gallary_image',
                ]);
            }
        }
        Logger::debug(sprintf('The Rake founded %d cover and gallery images from the feed', count($resources)));

        // Freeup memory
        unset($processor);

        return $resources;
    }

    protected function getContentImageResources()
    {
        if (empty($this->content)) {
            return [];
        }

        $resources = [];
        $processor = $this->tooth->getProcessor();
        $images    = $this->content->find('img');
        foreach ($images as $image) {
            $imageSrc     = static::callContentImageCallbacks($image->getAttribute('src'), $image);
            if (is_null($imageSrc)) {
                Logger::notice(sprintf(
                    'Could not find the src of the image of %s(#%d): %s',
                    $this->getNewType(),
                    $this->getNewGuid(),
                    $image->innerHtml
                ), [
                    'guid'     => $this->getGuid(),
                    'new_guid' => $this->getNewGuid(),
                    'new_type' => $this->getNewType(),
                    'tooth'    => $this->tooth->getId()
                ]);
                continue;
            }
            $convertedUrl = $processor->convertImageUrl($imageSrc);
            $imageLink    = Link::create($convertedUrl, $this->feedItem->guid);
            array_push($resources, [
                'guid' => $imageLink,
                'type' => 'content_image',
            ]);
            $image->setAttribute('src', (string)$imageLink);
        }
        Logger::debug(sprintf('The Rake founded %d images from content', count($resources)));

        // Freeup memory
        unset($processor, $images);
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
        Logger::debug(sprintf('The Rake founded %d links from content', count($resources)));

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

    public static function addContentImageCallback($id, $callable)
    {
        if (isset(static::$contentImageCallbacks[$id])) {
            Logger::notice(sprintf('The callback "%s" is already exists.', $id));
            return;
        } elseif (isset(static::$contentImageCallbacks[$id])) {
            Logger::notice(sprintf('The callback of "%s" is not callable.', $id));
            return;
        }
        static::$contentImageCallbacks[$id] = $callable;
    }

    protected static function callContentImageCallbacks($src, $imageDom)
    {
        if (empty(static::$contentImageCallbacks)) {
            return $src;
        }
        try {
            $outputSrc = $src;
            foreach (static::$contentImageCallbacks as $id => $callback) {
                Logger::debug(sprintf('Call the callback %s', $id));
                $outputSrc = call_user_func($callback, $outputSrc, $imageDom);
            }
            return $outputSrc;
        } catch (Throwable $e) {
            Logger::error(sprintf(
                '%s\n%s',
                $e->getMessage(),
                var_export(debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 2), true)
            ));
        }
        return $src;
    }
}
