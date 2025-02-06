<?php

namespace Ramphor\Rake\Types;

class CrawledUrl
{
    /**
     * @var int
     */
    protected $id;

    /**
     * @var string
     */
    protected $url;

    /**
     * @var string
     */
    protected $rakeId;

    /**
     * @var string|null
     */
    protected $toothId;

    /**
     * @var int
     */
    protected $crawled = 0;

    /**
     * @var int
     */
    protected $skipped = 0;

    /**
     * @var int
     */
    protected $retry = 0;

    /**
     * @var \DateTime|null
     */
    protected $createdAt;

    /**
     * @var \DateTime|null
     */
    protected $updatedAt;

    public function __get($name)
    {
        return $this->{$name};
    }
}
