<?php
namespace Ramphor\Rake\Feeds\Sitemap;

use SimpleXMLElement;
use Ramphor\Rake\Link;
use Ramphor\Rake\Abstracts\Feed;
use Ramphor\Rake\Facades\Request;
use Ramphor\Rake\Facades\Option;

class SitemapFeed extends Feed
{
    const NAME = 'sitemap';

    /**
     * @var boolean
     */
    protected $trimLastSplashURL = false;

    public function get_name()
    {
        return static::NAME;
    }

    public function execute()
    {
        $response = Request::sendRequest('GET', $this->url, array(
            'verify' => false,
        ));
        $xml_sitemap = new SimpleXMLElement($response->getBody());
        $parsedUrl   = parse_url($this->url);

        foreach ($xml_sitemap->url as $url) {
            if (empty($url->loc) || !($url->loc instanceof SimpleXMLElement)) {
                continue;
            }
            // fix domain
            $sitemapUrl = $url->loc;
            $parsedSitemapUrl = parse_url($sitemapUrl);
            if ($parsedSitemapUrl['host'] != $parsedUrl['host']) {
                $sitemapUrl = str_replace($parsedSitemapUrl['host'], $parsedUrl['host'], $sitemapUrl);
            }
            $link = new Link($sitemapUrl, null, $this->trimLastSplashURL);
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

    public function setUrl($url)
    {
        $this->url = $url;
    }
}
