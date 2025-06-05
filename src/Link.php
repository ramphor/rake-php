<?php

namespace Ramphor\Rake;

use Ramphor\Rake\Facades\Logger;

final class Link
{
    protected static $callbacks = [
        'parse' => [],
        'output' => [],
    ];

    protected $parsed        = false;
    protected $isSameSource  = false;
    protected $trimLastSlash = true;
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

    public static function create($url, $sourceUrl = null, $trimLastSlash = true)
    {
        return new static($url, $sourceUrl, $trimLastSlash);
    }

    public function __construct($url, $sourceUrl = null, $trimLastSlash = true)
    {
        $this->trimLastSlash = boolval($trimLastSlash);

        $this->setRawUrl($url);
        $this->setSourceUrl($sourceUrl);
    }

    public function __get($name)
    {
        if (property_exists(__CLASS__, $name)) {
            return $this->$name;
        }
    }

    protected function encodePath($path)
    {
        $paths = explode('/', $path);
        foreach ($paths as $index => $splittedPath) {
            if (strpos($splittedPath, '%') !== false) {
                $splittedPath = urldecode($splittedPath);
            }
            $paths[$index] = urlencode($splittedPath);
            $paths[$index] = str_replace('+', '%20', $paths[$index]);

            unset($splittedPath, $index);
        }
        return implode('/', $paths);
    }

    public function __toString()
    {
        $notify = false;
        if (!$this->parsed) {
            $notify = true;
            $this->parse();
        }

        $suffix = $this->encodePath($this->path);
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

        $outputUrl = $prefix . $this->host . $suffix;
        if (!empty(static::$callbacks['output'])) {
            $outputUrl = static::callOutputCallbacks($outputUrl, $this);
        }

        // Encode URL
        if (strpos($outputUrl, '%') !== false) {
            $outputUrl = urlencode($outputUrl);
            $outputUrl = str_replace(
                array('%2F','%3F','%3D','%26', '%3A'),
                array('/','?','=','&', ':'),
                $outputUrl
            );
        }

        // Decode existing encoded URL
        if (strpos($outputUrl, '%25') !== false) {
            $outputUrl = urldecode($outputUrl);
        }

        if ($notify && $outputUrl !== $this->rawUrl) {
            Logger::info(sprintf('The URL "%s" is changed to "%s"', $this->rawUrl, $outputUrl));
            $notify = false;
        }
        return $outputUrl;
    }

    /**
     * Native function of PHP has bug so this method is work around to fix it.
     *
     * Bug:
     * Raw URL: https://xanhvina.com/temp/uploaded-2024-tường hoa giả_lam-tuong-hoa-gia-cho-Mercedes-Benz-dai-tu (1)_rs1_1080x720.jpg"
     * Result: array(3) {
     *     'scheme' => "https"
     *     'host' => string(12) "xanhvina.com"
     *     'path' => string(102) "/temp/uploaded-2024-tư�_ng hoa giả_lam-tuong-hoa-gia-cho-Mercedes-Benz-dai-tu (1)_rs1_1080x720.jpg"
     * }
     * @return array
     */
    protected function parseUrl($url)
    {
        $schemeInfo = explode('//', $url);
        $ret = [];
        $hasDomain = false;
        if (isset($schemeInfo[1])) {
            $ret['scheme'] = trim($schemeInfo[0], ':');
            $hasDomain = true;
        }
        $domainAndPath = isset($schemeInfo[1]) ? $schemeInfo[1] : $schemeInfo[0];
        $query = '';

        if (strpos($domainAndPath, '?')) {
            $splitByQuestionCharacter = explode('?', $domainAndPath);
            $domainAndPath = $splitByQuestionCharacter[0];
            $query = $splitByQuestionCharacter[1];
        }

        $path = $domainAndPath;
        if ($hasDomain) {
            $paths = explode('/', $domainAndPath);
            $domain = array_shift($paths);

            // to start path with / character
            array_unshift($paths, '');

            $path = implode('/', $paths);

            $ret['host'] = $domain;
        }
        $ret['path'] = $path;

        if (!empty($query)) {
            $ret['query'] = $query;
        }

        return $ret;
    }

    public function setRawUrl($url)
    {
        if ($this->trimLastSlash) {
            $this->rawUrl = rtrim($url, '/');
        } else {
            $this->rawUrl = $url;
        }
    }

    public function setSourceUrl($sourceUrl)
    {
        $this->sourceUrl = $sourceUrl;
    }

    public function parse()
    {
        $parsedUrl = $this->parseUrl($this->rawUrl);
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
