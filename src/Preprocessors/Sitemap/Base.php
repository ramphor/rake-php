<?php
namespace Ramphor\Rake\Preprocessors\Sitemap;

use Ramphor\Rake\Abstracts\AbstractPreprocessor;

abstract class Base extends AbstractPreprocessor
{
    protected $namespaces = [];
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
