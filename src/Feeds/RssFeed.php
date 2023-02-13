<?php
namespace Ramphor\Rake\Feeds;

use Ramphor\Rake\Constracts\Feeds\RssFeedConstract;

class RssFeed extends XmlFeed implements RssFeedConstract {
    protected $feedUrl;

    /**
     * @param string $rssUrl
     *
     * @return self
     */
    public function setRssUrl($rssUrl):self {
        return $this;
    }
}
