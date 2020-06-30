<?php
namespace Ramphor\Rake\Feeds\Sitemap;

use SimpleXMLElement;
use Ramphor\Rake\Link;
use Ramphor\Rake\Abstracts\Feed;

class Sitemap extends Feed
{
    protected $httpClient;

    public function execute()
    {
        $xml_str = $this->httpClient->request('GET', $this->id);
        $xml_sitemap = new SimpleXMLElement($xml_str);

        foreach ($xml_sitemap->url as $url) {
            if (empty($url->loc) || !($url->loc instanceof SimpleXMLElement)) {
                continue;
            }

            $link = new Link((string)$url->loc);
            $this->insertCrawlUrl($link);
        }
    }
}
