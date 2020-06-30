<?php
namespace Ramphor\Rake\Feeds\Sitemap;

use Ramphor\Rake\Abstracts\AbstractFeed;

abstract class Base extends AbstractFeed
{
    protected $url;

    public function __construct($sitemapURL)
    {
        $this->url = $sitemapURL;
    }

    public function getId()
    {
        return $this->url;
    }
}
