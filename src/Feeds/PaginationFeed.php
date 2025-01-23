<?php

namespace Ramphor\Rake\Feeds;

use Ramphor\Rake\Abstracts\Feed;
use Ramphor\Rake\Constracts\Feeds\PaginationFeedConstract;
use Ramphor\Rake\Facades\Request;
use Ramphor\Rake\Types\PagedType;
use PHPHtmlParser\Dom;
use Ramphor\Rake\Facades\Option;
use Ramphor\Rake\Link;

class PaginationFeed extends Feed implements PaginationFeedConstract
{
    const NAME = 'pagination';

    protected $pagedURL;

    protected $queryParamName;
    protected $currentPage = 1;
    protected $dataSource;
    protected $pagedType;

    /**
     * from this block started setting for pagination format is path based.
     */
    protected $paginationFormat;


    /**
     * from this block started setting for loop content
     */
    protected $linkItemsSelector;

    // Default link attribute value is `href`. Commonly used for <a> tag
    protected $linkItemValueAttribute = 'href';

    /**
     * from this block started setting for DOM object
     */
    protected $responseDOM;


    public function __construct($feedId, $pagedURL)
    {
        $this->id = $feedId;

        $this->pagedType = PagedType::QUERY();

        $this->pagedURL = $pagedURL;
    }

    /**
     * Set pagination type (query param or path based)
     *
     * @param PagedType $type
     * @return self
     */
    public function setPagedType(PagedType $type): self
    {
        $this->pagedType = $type;
        return $this;
    }

    public function setPagedParamName($paramName): self
    {
        if ($this->pagedType->equals(PagedType::PATH())) {
            throw new \Exception('Cannot set query param name for path based pagination');
        }
        $this->queryParamName = $paramName;

        return $this;
    }

    protected function isEmptyPage($content): bool
    {
        if (empty($content)) {
            return true;
        }

        $doc = new \DOMDocument();
        @$doc->loadHTML($content);
        $xpath = new \DOMXPath($doc);

        foreach ($this->dataSource->empty_selectors as $selector) {
            $nodes = $xpath->query("//*[contains(@class, '" . str_replace('.', '', $selector) . "')]");
            if ($nodes->length > 0) {
                return true;
            }
        }

        $itemNodes = $xpath->query("//div[contains(@class, 'estate-item')]");
        return $itemNodes->length === 0;
    }

    protected function buildPageUrl($url): string
    {
        $parsedUrl = parse_url($url);

        parse_str($parsedUrl['query'], $params);

        // delete query
        unset($parsedUrl['query']);

        $feedId = sprintf('feed_%s_executed', $this->id);

        $feedOptions = Option::get($feedId, []);

        // var_dump($feedOptions);die;

        // $this->setCurrentPage();

        $params[$this->queryParamName] = $this->currentPage;

        return sprintf('%s://%s%s?%s', $parsedUrl['scheme'], $parsedUrl['host'], $parsedUrl['path'], http_build_query($params));
    }

    private function buildQueryUrl(string $url, int $page): string
    {
        $separator = parse_url($url, PHP_URL_QUERY) ? '&' : '?';
        return $url . $separator . $this->dataSource->page_param . '=' . $page;
    }

    private function buildPathUrl(string $url, int $page): string
    {
        return rtrim($url, '/') . '/' . $this->dataSource->page_param . '/' . $page;
    }

    public function hasResponse()
    {
        return true;
    }

    public function fetch()
    {
        // Get current page URL
        $currentUrl = $this->buildPageUrl($this->pagedURL);

        $response = Request::sendRequest('GET', $currentUrl);

        $this->responseDOM = new Dom();
        $this->responseDOM->loadStr($response->getBody()->getContents());

        $linkItems = $this->responseDOM->find($this->linkItemsSelector);

        foreach ($linkItems as $linkItem) {
            $linkItemValue = $linkItem->getAttribute($this->linkItemValueAttribute);
            $this->insertCrawlUrl(new Link($linkItemValue, $currentUrl, $this->trimLastSplashURL));
        }
    }

    public function setCurrentPage(int $page)
    {
        $this->currentPage = $page;
        return $this;
    }

    public function getCurrentPage(): int
    {
        return $this->currentPage;
    }

    public function getBaseUrl(): string
    {
        return $this->dataSource->full_url;
    }

    protected function request($url)
    {
        return Request::get($url);
    }

    public function get_name()
    {
        return static::NAME;
    }

    public function execute()
    {
    }

    public function valid()
    {
        return true;
    }

    public function next()
    {
        $optionKey = sprintf('feed_%s_executed', $this->id);
        Option::update($optionKey, [
            'current_page' => $this->currentPage,
            'next_page' => $this->currentPage + 1,
            'last_imported_page' => $this->currentPage,
        ]);
    }

    public function rewind()
    {
        $optionKey = sprintf('feed_%s_executed', $this->id);
        Option::update($optionKey, [
            'current_page' => 1,
            'next_page' => 1,
            'last_imported_page' => 1,
        ]);
    }

    public function pageHasContent($content): bool
    {
        return true;
    }

    public function setContentCheckerCallback(callable $callback): self
    {
        return $this;
    }

    public function setPaginationFormat(string $format): self
    {
        $this->paginationFormat = $format;

        return $this;
    }

    public function setLinkItemsSelector($selector): self
    {
        $this->linkItemsSelector = $selector;
        return $this;
    }

    public function getLinkItemsSelector(): string
    {
        return $this->linkItemsSelector;
    }


    public function setLinkItemValueAttribute($attribute): self
    {
        $this->linkItemValueAttribute = $attribute;
        return $this;
    }

    public function getLinkItemValueAttribute(): string
    {
        return $this->linkItemValueAttribute;
    }
}
