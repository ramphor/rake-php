<?php
namespace Ramphor\Rake\Feeds\Sitemap;

use SimpleXMLElement;
use Ramphor\Rake\Link;
use Ramphor\Rake\Abstracts\Feed;
use Ramphor\Rake\Facades\Client;

class Sitemap extends Feed
{
    public function execute()
    {
        $response = Client::request('GET', $this->id);
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
        $executed = $this->getOption('executed', false);

        return empty($executed);
    }

    public function next()
    {
        $this->updateOption('executed', true);
    }

    public function rewind()
    {
        $this->updateOption('executed', false);
    }
}
