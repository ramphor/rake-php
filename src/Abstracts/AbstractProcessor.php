<?php
namespace Ramphor\Rake\Abstracts;

use Monolog\Logger;
use Ramphor\Rake\Constracts\Processor;
use Ramphor\Rake\DataSource\FeedItem;

abstract class AbstractProcessor implements Processor
{
    protected $crawlURLs = false;

    protected $feedItem;

    public function __construct($feedItem)
    {
        $this->feedItem = $feedItem;
    }

    public function validateFeedItem(): bool
    {
        return true;
    }

    public function crawlURLs()
    {
    }

    public function writeLog($logMessage, $data = [], $logLevel = Logger::WARNING)
    {
    }
}
