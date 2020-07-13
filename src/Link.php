<?php
namespace Ramphor\Rake;

final class Link
{
    protected $parsed = false;
    protected $rawUrl;
    protected $sourceUrl;

    protected $scheme;
    protected $host;
    protected $port;
    protected $user;
    protected $pass;
    protected $path;
    protected $query;
    protected $fragment;

    public function __construct($url, $sourceUrl = null)
    {
        $this->setRawUrl($url);
        $this->setSourceUrl($sourceUrl);
    }

    public function __get($name)
    {
        if (method_exists(__CLASS__, $name)) {
            return $this->$name;
        }
    }

    public function setRawUrl($url)
    {
        $this->rawUrl = rtrim($url, '/');
    }

    public function setSourceUrl($sourceUrl)
    {
        $this->sourceUrl = $sourceUrl;
    }

    public function parse()
    {
        $parsedUrl = parse_url($this->rawUrl);
        foreach ($parsedUrl as $key => $value) {
            $this->$key = $value;
        }

        if (!empty($this->sourceUrl)) {
            $parsedSourceUrl = parse_url($this->sourceUrl);
            if (empty($this->host) && isset($parsedSourceUrl['host'])) {
                $this->host = $parsedSourceUrl['host'];
            }
            if (empty($this->scheme) && isset($parsedSourceUrl['scheme'])) {
                $this->scheme = $parsedSourceUrl['scheme'];
            }
        }
        $this->parsed = true;
    }

    public function __toString()
    {
        if (!$this->parsed) {
            $this->parse();
        }
        $suffix = $this->path;
        if ($this->query) {
            $suffix .= '?' . $this->query;
        }
        if ($this->fragment) {
            $suffix .= '#' . $this->fragment;
        }

        if (empty($this->host)) {
            return $suffix;
        }

        $prefix = sprintf('%s://', empty($this->scheme) ? 'http' : $this->scheme);
        if ($this->user) {
            $account = $this->user;
            if ($this->pass) {
                $account .= ':' . $this->pass;
            }
            $prefix .= $account . '@';
        }
        return $prefix . $this->host . $suffix;
    }
}
