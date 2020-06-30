<?php
namespace Ramphor\Rake\Preprocessors\Sitemap;

use Ramphor\Rake\Abstracts\AbstractPreprocessor;

abstract class Base extends AbstractPreprocessor
{
    protected $teethId;
    protected $url;

    public function __construct($teethId, $sitemapURL)
    {
        $this->teethId = $teethId;
        $this->url = $sitemapURL;
    }

    public function getId()
    {
        return $this->url;
    }
}
