<?php
namespace Ramphor\Rake\Abstracts;

use Monolog\Logger;
use Ramphor\Rake\Constracts\Processor as ProcessorConstract;
use Ramphor\Rake\DataSource\FeedItem;

abstract class Processor extends TemplateMethod implements ProcessorConstract
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
