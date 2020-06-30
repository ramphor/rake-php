<?php
namespace Ramphor\Rake\Abstracts;

use Monolog\Logger;
use Ramphor\Rake\Constracts\Processor;
use Ramphor\Rake\DataSource\FeedItem;

abstract class AbstractProcessor extends TemplateMethod implements Processor
{
    protected $toothItem;

    public function __construct($toothItem)
    {
        $this->toothItem = $toothItem;
    }

    public function validateFeedItem(): bool
    {
        return true;
    }

    public function writeLog($logMessage, $data = [], $logLevel = Logger::WARNING)
    {
    }
}
