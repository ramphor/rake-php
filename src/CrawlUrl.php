<?php
namespace Ramphor\Rake;

class CrawlUrl {
    protected $crawlSuccess = false;

    protected $urlId;

    public static function createFromResult(ProcessResult $processResult): self {
        $crawlUrl = new CrawlUrl($processResult->getUrlDbId());
        $crawlUrl->crawlSuccess = $processResult->isSuccess();
    }

    public function __construct($urlId) {
        $this->urlId = $urlId;
    }

    public function sync() {
    }

    public function skipped() {
    }
}
