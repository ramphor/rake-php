<?php
namespace Ramphor\Rake\DataSource;

class FeedItem
{
    protected $originalData;

    public $key;
    public $title;
    public $content;
    public $createdAt;
    public $updatedAt;
    public $guid;
    public $categories;
    public $tags;
    public $coverImage;
    public $status;

    public function __construct($originalData)
    {
        $this->originalData = $originalData;
    }

    public function getOriginalData()
    {
        return $this->originalData;
    }
}
