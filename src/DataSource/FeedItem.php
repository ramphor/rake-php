<?php
namespace Ramphor\Rake\DataSource;

class FeedItem
{
    protected $originalData;

    public $key;
    public $title;
    public $content;
    public $createdAt;
    public $publishedAt;
    public $updatedAt;
    public $guid;
    public $categories;
    public $tags;
    public $coverImage;
    public $imageGallery;
    public $status;
    public $metas;

    public function __construct($originalData)
    {
        $this->originalData = $originalData;
    }

    public function getOriginalData()
    {
        return $this->originalData;
    }
}
