<?php
namespace Ramphor\Rake\Constracts\Feeds;

use Ramphor\Rake\Constracts\Feed;

interface PagingFeedInterface extends Feed
{
    /**
     * @param string $url The URL will be parsed to detect domain, scheme, path
     *
     * @return static
     */
    public function parseUrl($url);

    /**
     * @param string $urlFormat The format of URL will be use to generate data feed URL
     *
     * @return static
     */
    public function setPaginateFormat($urlFormat);
}
