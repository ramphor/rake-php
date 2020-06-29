<?php
namespace Ramphor\Rake\Preprocessors\Sitemap;

use SimpleXMLElement;

class Sitemap extends Base
{
    protected $httpClient;

    protected $namespaces = [
        'xmlns' => 'http://www.sitemaps.org/schemas/sitemap/0.9',
        'xmlns:image' => 'http://www.google.com/schemas/sitemap-image/1.1'
    ];

    public function execute()
    {
        $xml_str = $this->httpClient->request('GET', $this->url);
        $xml_sitemap = new SimpleXMLElement($xml_str);
    }
}
