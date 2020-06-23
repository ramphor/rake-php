<?php
namespace Ramphor\Rake;

use Ramphor\Rake\Constracts\Processor as ProcessorConstract;
use Ramphor\Rake\Data\FeedItem;

abstract class Processor implements ProcessorConstract
{
    public const LOG_DEBUG = 100;
    public const LOG_INFO = 200;
    public const LOG_NOTICE = 250;
    public const LOG_WARNING = 300;
    public const LOG_ERROR = 400;
    public const LOG_CRITICAL = 500;
    public const LOG_ALERT = 550;
    public const LOG_EMERGENCY = 600;

    protected $feedItem;

    public function __construct(FeedItem $feedItem)
    {
        $this->feedItem = $feedItem;
    }
}
