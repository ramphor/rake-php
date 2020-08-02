<?php
namespace Ramphor\Rake;

use Ramphor\Rake\Facades\Logger;

final class Link
{
    protected static $callbacks = [
        'parse' => [],
        'output' => [],
    ];

    protected $parsed       = false;
    protected $isSameSource = false;
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

    public static function create($url, $sourceUrl = null)
    {
        return new static($url, $sourceUrl);
    }

    public function __construct($url, $sourceUrl = null)
    {
        $this->setRawUrl($url);
        $this->setSourceUrl($sourceUrl);
    }

    public function __get($name)
    {
        if (property_exists(__CLASS__, $name)) {
            return $this->$name;
        }
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
        $ouputUrl = $prefix . $this->host . $suffix;
        ;
        if (empty(static::$callbacks['output'])) {
            return $ouputUrl;
        }
        return static::callOutputCallbacks($output, $this);
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
        $parsedSourceUrl = [];
        if (!empty($this->sourceUrl)) {
            $parsedSourceUrl = parse_url($this->sourceUrl);
        }

        if (!$this->host) {
            $this->isSameSource = true;
            if (isset($parsedSourceUrl['host'])) {
                $this->host = $parsedSourceUrl['host'];
            }
        } else {
            $this->isSameSource = empty($parsedSourceUrl['host']) || $this->host == $parsedSourceUrl['host'];
        }

        if (empty($this->scheme) && isset($parsedSourceUrl['scheme'])) {
            $this->scheme = $parsedSourceUrl['scheme'];
        }

        if (!empty(static::$callbacks['parse'])) {
            static::callParseCallbacks($this);
        }

        $this->parsed = true;
    }

    public function isSameSource()
    {
        if (!$this->parsed) {
            $this->parse();
        }

        return (bool) $this->isSameSource;
    }

    public static function addCallback($id, $callable, $hook = 'output')
    {
        if (!is_callable($callable)) {
            Logger::warning(sprintf('Register callback "%s" with param is not callable', $id));
            return;
        }
        if (!isset(static::$callbacks[$hook][$id])) {
            static::$callbacks[$hook][$id] = $callable;
        }
    }

    protected static function callParseCallbacks(&$linkInstance)
    {
        foreach (static::$callbacks['parse'] as $callback) {
            $callback($linkInstance);
        }
        return $linkInstance;
    }

    protected static function callOutputCallbacks($output, $linkInstance)
    {
        foreach (static::$callbacks['parse'] as $callback) {
            $output = $callback($output, $linkInstance);
        }
        return $output;
    }
}
