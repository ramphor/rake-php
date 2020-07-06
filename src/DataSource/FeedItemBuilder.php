<?php
namespace Ramphor\Rake\DataSource;

use Ramphor\Rake\Constracts\FeedItemBuilder as FeedItemBuilderConstract;

class FeedItemBuilder implements FeedItemBuilderConstract
{
    protected $mappingFields;
    protected $feedItem;

    public function __construct($mappingFields = [])
    {
        if (!empty($mappingFields)) {
            $this->setMappingFields($mappingFields);
        }
    }

    public function setMappingFields($mappingFields)
    {
        $this->mappingFields = $mappingFields;
    }

    public function newItem($data)
    {
        $this->feedItem = new FeedItem($data);
    }

    public function build()
    {
    }

    public function getFeedItem(): FeedItem
    {
        return $this->feedItem;
    }
}
