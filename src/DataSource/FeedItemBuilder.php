<?php
namespace Ramphor\Rake\DataSource;

use Ramphor\Rake\Constracts\FeedItemBuilder as FeedItemBuilderConstract;
use Sunra\PhpSimple\HtmlDomParser;

class FeedItemBuilder implements FeedItemBuilderConstract
{
    protected $originalData;
    protected $mappingFields;
    protected $feedItem;
    protected $dataType;
    protected $document;

    public function __construct($mappingFields = [], $dataType = null)
    {
        if (!empty($mappingFields)) {
            $this->setMappingFields($mappingFields);
        }
        if (!is_null($dataType)) {
            $this->setDataType($dataType);
        }
    }

    public function setDataType($dataType)
    {
        $this->dataType = $dataType;
    }

    public function setMappingFields($mappingFields)
    {
        $this->mappingFields = $mappingFields;
    }

    public function newItem($data)
    {
        $this->originalData = $data;
        var_dump($this->dataType);
        die;
        if ($this->dataType === 'html') {
            $this->document = HtmlDomParser::str_get_html($data["body"]);
        }

        var_dump($this->document);
        die;

        $this->feedItem = new FeedItem();
    }

    public function build()
    {
    }

    public function getFeedItem(): FeedItem
    {
        return $this->feedItem;
    }
}
