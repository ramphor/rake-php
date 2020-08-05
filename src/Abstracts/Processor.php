<?php
namespace Ramphor\Rake\Abstracts;

use Ramphor\Rake\Link;
use PHPHtmlParser\Dom;
use PHPHtmlParser\Dom\HtmlNode;
use PHPHtmlParser\Dom\Tag;
use Ramphor\Rake\Constracts\Processor as ProcessorConstract;
use Ramphor\Rake\Facades\Client;
use Ramphor\Rake\Facades\Logger;
use Ramphor\Rake\DataSource\FeedItem;

abstract class Processor implements ProcessorConstract
{
    protected $id;
    protected $tooth;
    protected $feedItem;
    protected $importedId;

    protected $cleanContentAttributes = true;
    protected $crawlLinks = false;

    protected static $ignoreAttributes = ['style'];
    protected static $doNotReplaceTags = ['a', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'];
    protected static $convertRules = [
        '[style*="font-weight: bold"]' => 'strong',
        '[style*="font-style: italic"]' => 'em',
    ];

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
        // Reset imported ID on each processing feed item
        $this->importedId = null;

        // Set feed item to process data
        $this->feedItem = $feedItem;
    }

    protected function checkImageIsFound($imageUrl)
    {
        try {
            $response = Client::request('HEAD', $imageUrl, ['http_errors' => false, 'allow_redirects' => true]);
            return $response->getStatusCode() < 400;
        } catch (\Exception $e) {
            Logger::warning($e->getMessage(), [
                'image_url' => $imageUrl,
            ]);
        }
        return false;
    }

    /**
     * Convert image URL to real URL if need
     * Default Rake doesn't do anything image URL
     * @param string $imageUrl
     * @return string the image URL after convert
     */
    public function convertImageUrl($imageUrl)
    {
        return $imageUrl;
    }

    protected function convertStylesheets($document)
    {
        foreach (static::$convertRules as $rule => $tagName) {
            $htmlNodes = $document->find($rule);
            if (count($htmlNodes) <= 0) {
                continue;
            }

            foreach ($htmlNodes as $htmlNode) {
                $wrapTag = new HtmlNode(new Tag($htmlNode->tag->name()));
                foreach ($htmlNode->getAttributes() as $attribute => $value) {
                    if (in_array($attribute, static::$ignoreAttributes)) {
                        continue;
                    }
                    $wrapTag->setAttribute($attribute, $value);
                }
                $newTag = new HtmlNode(new Tag($tagName));
                foreach ($htmlNode->getChildren() as $children) {
                    $newTag->addChild($children);
                }
                if (in_array($htmlNode->tag->name(), static::$doNotReplaceTags)) {
                    $wrapTag->addChild($newTag);
                    $htmlNode->getParent()->replaceChild($htmlNode->id(), $wrapTag);
                } else {
                    $htmlNode->getParent()->replaceChild($htmlNode->id(), $newTag);
                }
            }
        }
        return $document;
    }

    /**
     * Remove the attributes in HTML of the post content
     *
     * @link https://stackoverflow.com/questions/3026096/remove-all-attributes-from-an-html-tag
     * @param string $html
     * @return string
     */
    public function removeContentAttributes($document)
    {
        foreach (static::$ignoreAttributes as $attribute) {
            foreach ($document->find(sprintf('[%s]', $attribute)) as $htmlNode) {
                $htmlNode->tag->removeAttribute($attribute);
            }
        }
        return $document;
    }

    /**
     * Cleanup the HTML before import to your system
     *
     * @todo Convert styles to strong and em tags.
     * @todo Remove all attribute of the tags
     *
     * @param string $content The post content
     * @return string The output HTML after cleanup
     */
    public function cleanupContentBeforeImport($content)
    {
        if (!$this->cleanContentAttributes) {
            return $content;
        }

        $document = new Dom();
        $document->load((string)$content);

        // Convert common stylesheet
        $document = $this->convertStylesheets($document);
        $document = $this->removeContentAttributes($document);

        // Return the clean content
        return $document->innerHtml;
    }
}
