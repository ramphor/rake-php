<?php

namespace Ramphor\Rake\Feeds;

use Ramphor\Rake\Abstracts\Feed;
use Ramphor\Rake\Constracts\Feeds\PaginationFeedConstract;
use Ramphor\Rake\Facades\Request;
use Ramphor\Rake\Link;
use Ramphor\Rake\Types\PagedType;

class PaginationFeed extends Feed implements PaginationFeedConstract
{
    const NAME = 'pagination';

    protected $currentPage = 1;
    protected $dataSource;
    protected $pagedType;

    public function __construct()
    {
        $this->pagedType = PagedType::QUERY();
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

    protected function buildPageUrl($page): string
    {
        $url = $this->dataSource->full_url;

        if ($this->pagedType->equals(PagedType::QUERY())) {
            return $this->buildQueryUrl($url, $page);
        }
        return $this->buildPathUrl($url, $page);
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

    public function fetch(): FeedResult
    {
        $result = new FeedResult();

        // Get current page URL
        $currentUrl = $this->buildPageUrl($this->currentPage);

        // Fetch content
        $response = $this->request($currentUrl);
        $content = $response->getBody()->getContents();

        // Check if page is empty
        if ($this->isEmptyPage($content)) {
            return $result;
        }

        // Add current page content to result
        $result->setContent($content);

        // Add next page URL
        $nextPage = $this->currentPage + 1;
        $nextUrl = $this->buildPageUrl($nextPage);
        $result->addLink(new Link($nextUrl));

        // Add previous page URL if not first page
        if ($this->currentPage > 1) {
            $prevPage = $this->currentPage - 1;
            $prevUrl = $this->buildPageUrl($prevPage);
            $result->addLink(new Link($prevUrl));
        }

        // Increment current page
        $this->currentPage++;

        return $result;
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
    }

    public function next()
    {
    }

    public function rewind()
    {
    }
}
