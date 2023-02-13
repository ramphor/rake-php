<?php
namespace Ramphor\Rake\Constracts\Feeds;

interface RssFeedConstract extends XmlFeedConstract {
    /**
     * @param string $rssUrl
     *
     * @return self
     */
    public function setRssUrl($rssUrl):self;
}
