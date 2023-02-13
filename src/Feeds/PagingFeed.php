<?php
namespace Ramphor\Rake\Feeds;

use Ramphor\Rake\Abstracts\Feed;
use Ramphor\Rake\Constracts\Feeds\PagingFeedInterface;

class PagingFeed extends Feed implements PagingFeedInterface
{

    public function get_name()
    {
        return 'paging_feed';
    }

    public function execute()
    {
    }

    public function valid()
    {
    }

    public function next()
    {
    }

    public function rewind()
    {
    }

    /**
     * @param string $url The URL will be parsed to detect domain, scheme, path
     *
     * @return self
     */
    public function parseUrl($url)
    {
    }

    /**
     * @param string $urlFormat The format of URL will be use to generate data feed URL
     *
     * @return static
     */
    public function setPaginateFormat($urlFormat)
    {
    }
}
