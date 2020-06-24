<?php
namespace Ramphor\Rake;

use Ramphor\Rake\Constracts\Feed as FeedConstract;

abstract class Feed implements FeedConstract
{
    public const FORMAT_CSV      = 'csv';
    public const FORMAT_XML      = 'xml';
    public const FORMAT_JSON     = 'json';
    public const FORMAT_SITEMAP  = 'sitemap';
    public const FORMAT_HTML     = 'html';

    protected $feedFormat;

    public function setFeedFormat($format)
    {
        $this->feedFormat = $format;
    }
}
