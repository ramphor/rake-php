<?php
namespace Ramphor\Rake\DataSource;

class FeedItem
{
    protected $guid;

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

    public function __construct($guid = null)
    {
        $this->guid = $guid;
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
