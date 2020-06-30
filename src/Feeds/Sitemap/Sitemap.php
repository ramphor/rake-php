<?php
namespace Ramphor\Rake\Feeds\Sitemap;

use SimpleXMLElement;
use Ramphor\Rake\Link;

class Sitemap extends Base
{
    protected $httpClient;

    public function execute()
    {
        $xml_str = $this->httpClient->request('GET', $this->url);
        $xml_sitemap = new SimpleXMLElement($xml_str);

        foreach ($xml_sitemap->url as $url) {
            if (empty($url->loc) || !($url->loc instanceof SimpleXMLElement)) {
                continue;
            }
            $link = new Link($url->loc->__toString());

            $this->insertCrawlUrl($link);
        }
    }
}
