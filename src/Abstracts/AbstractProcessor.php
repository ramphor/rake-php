<?php
namespace Ramphor\Rake\Abstracts;

use Ramphor\Rake\Constracts\Processor;
use Ramphor\Rake\DataSource\FeedItem;

abstract class AbstractProcessor implements Processor
{
    public const LOG_DEBUG     = 100;
    public const LOG_INFO      = 200;
    public const LOG_NOTICE    = 250;
    public const LOG_WARNING   = 300;
    public const LOG_ERROR     = 400;
    public const LOG_CRITICAL  = 500;
    public const LOG_ALERT     = 550;
    public const LOG_EMERGENCY = 600;

    protected $feedItem;

    public function __construct(FeedItem $feedItem)
    {
        $this->feedItem = $feedItem;
    }

    public function validateFeedItem(): bool
    {
        return true;
    }
}
