<?php
namespace Ramphor\Rake\DataSource;

class FeedItem
{
    protected $guid;

    public $title;
    public $content;
    public $createdAt;
    public $publishedAt;
    public $updatedAt;
    public $categories;
    public $tags;
    public $coverImage;
    public $imageGallery;
    public $status;
    public $metas;

    public function __construct($guid = null)
    {
        $this->guid = $guid;
    }

    public function deleteGUID()
    {
        unset($this->guid);
    }

    public function isValid()
    {
        return !empty($this->guid);
    }
}
