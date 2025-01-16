<?php

namespace Ramphor\Rake\Constracts\Feeds;

use Ramphor\Rake\Constracts\Feed;
use Ramphor\Rake\Types\PagedType;

interface PaginationFeedConstract extends Feed
{
    public function pageHasContent($content): bool;

    public function setContentCheckerCallback(callable $callback);

    public function setPaginationFormat(string $format);

    public function setPagedType(PagedType $type);
}
