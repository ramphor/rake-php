<?php
namespace Ramphor\Rake\Feeds\Sitemap;

use Ramphor\Rake\Abstracts\AbstractPreprocessor;

abstract class Base extends AbstractPreprocessor
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
