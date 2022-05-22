<?php
namespace Ramphor\Rake\DataSource;

class FeedItem
{
    /**
     * @var string
     */
    protected $guid;
    protected $originalId;

    /**
     * @var int
     */
    protected $urlDbId;

    /**
     * @var string
     */
    protected $title;
    protected $content;
    protected $createdAt;
    protected $publishedAt;
    protected $updatedAt;

    /**
     * @var string[]
     */
    protected $categories;

    /**
     * @var string[]
     */
    protected $tags;

    /**
     * @var string
     */
    protected $coverImage;

    /**
     * @var string[]
     */
    protected $galleryImages;

    protected $status;
    protected $metas;
    protected $slug;

    /**
     * Product properties
     */
    protected $productName;
    protected $productCategories;
    protected $productPrice;
    protected $productImages;

    protected $productCategoryName;
    protected $productCategoryDesc;
    protected $productCategoryShortDesc;


    protected $pageTitle;
    protected $pageContent;

    protected $breadcrumb;

    protected $errorType;

    public function __construct($guid = null, $urlDbId = null)
    {
        $this->guid = $guid;
        $this->urlDbId = $urlDbId;
    }

    public function __get($name)
    {
        if (property_exists($this, $name)) {
            return $this->$name;
        }
        return $this->getMeta($name);
    }

    public function deleteGuid()
    {
        $this->guid = null;
    }

    public function isValid()
    {
        return !empty($this->guid);
    }

    public function setError($type = 'skip')
    {
        $this->errorType = $type;
    }

    public function isSkipped()
    {
        return $this->errorType === 'skip';
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
