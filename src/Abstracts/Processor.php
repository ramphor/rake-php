<?php
namespace Ramphor\Rake\Abstracts;

use Ramphor\Rake\Link;
use Ramphor\Rake\Constracts\Processor as ProcessorConstract;
use Ramphor\Rake\Facades\Client;
use Ramphor\Rake\Facades\Logger;
use Ramphor\Rake\DataSource\FeedItem;

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
}
