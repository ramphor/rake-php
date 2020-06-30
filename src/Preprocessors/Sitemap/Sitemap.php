<?php
namespace Ramphor\Rake\Preprocessors\Sitemap;

use SimpleXMLElement;

class Sitemap extends Base
{
    protected $httpClient;

    public function execute()
    {
        $xml_str = $this->httpClient->request('GET', $this->url);
        $xml_sitemap = new SimpleXMLElement($xml_str);
        $xml_sitemap
    }
}
