<?php
namespace Ramphor\Rake\Abstracts;

use Monolog\Logger;
use Ramphor\Rake\Link;
use Ramphor\Rake\Constracts\Processor as ProcessorConstract;
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
            // Will logging later
        }
        return false;
    }
}
