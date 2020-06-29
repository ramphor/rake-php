<?php
namespace Ramphor\Rake\Preprocessors\Sitemap;

use Ramphor\Rake\Abstracts\AbstractPreprocessor;

abstract class Base extends AbstractPreprocessor
{
    protected $namespaces = [];
    protected $url;

    public function __construct($sitemapURL, $namespaces = null)
    {
        $this->url = $sitemapURL;

        if (!is_null($namespaces)) {
            $this->addNamespaces($namespaces);
        }
    }

    public function addNamespaces($namespaces)
    {
        foreach ($namespaces as $namespace => $url) {
            if (!isset($this->namespaces[$namespace])) {
                $this->namespaces[$namespace] = $url;
            }
        }
    }

    public function getId()
    {
        return $this->url;
    }
}
