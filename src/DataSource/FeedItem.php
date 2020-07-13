<?php
namespace Ramphor\Rake\DataSource;

class FeedItem
{
    protected $guid;
    protected $urlDbId;

    protected $title;
    protected $content;
    protected $createdAt;
    protected $publishedAt;
    protected $updatedAt;
    protected $categories;
    protected $tags;
    protected $coverImage;
    protected $galleryImages;
    protected $status;
    protected $metas;

    protected $skipped = false;

    public function __construct($guid = null, $urlDbId = null)
    {
        $this->guid = $guid;
        $this->urlDbId = $urlDbId;
    }

    public function __get($name)
    {
        if (isset($this->$name)) {
            return $this->$name;
        }
    }

    public function deleteGuid()
    {
        unset($this->guid);
    }

    public function isValid()
    {
        return !empty($this->guid);
    }

    public function setSkipped()
    {
        $this->skipped = true;
    }

    public function isSkipped()
    {
        return $this->skipped;
    }

    public function setProperty($propName, $propValue)
    {
        if (property_exists(__CLASS__, $propName)) {
            $this->$propName = $propValue;
        } else {
            $this->addMeta($propName, $propValue);
        }
    }

    public function addMeta($metaKey, $metaValue)
    {
        $this->metas[$metaKey] = $metaValue;
    }

    public function getMeta($metaKey, $defaultValue = null)
    {
        if (isset($this->metas[$metaKey])) {
            return $this->metas[$metaKey];
        }

        return $defaultValue;
    }
}
