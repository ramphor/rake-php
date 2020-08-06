<?php
namespace Ramphor\Rake\Feeds\Sitemap;

use SimpleXMLElement;
use Ramphor\Rake\Link;
use Ramphor\Rake\Abstracts\Feed;
use Ramphor\Rake\Facades\Request;
use Ramphor\Rake\Facades\Option;

class Sitemap extends Feed
{
    public function execute()
    {
        $response = Request::sendRequest('GET', $this->id);
        $xml_sitemap = new SimpleXMLElement($response->getBody());

        foreach ($xml_sitemap->url as $url) {
            if (empty($url->loc) || !($url->loc instanceof SimpleXMLElement)) {
                continue;
            }

            $link = new Link((string)$url->loc);
            $this->insertCrawlUrl($link);
        }
    }

    public function valid()
    {
        $optionKey = sprintf('feed_%s_executed', $this->id);
        $executed  = Option::get($optionKey, false);

        return empty($executed);
    }

    public function next()
    {
        $optionKey = sprintf('feed_%s_executed', $this->id);
        Option::update($optionKey, true);
    }

    public function rewind()
    {
        $optionKey = sprintf('feed_%s_executed', $this->id);
        Option::update($optionKey, false);
    }
}
