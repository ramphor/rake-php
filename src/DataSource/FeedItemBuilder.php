<?php
namespace Ramphor\Rake\DataSource;

use Ramphor\Rake\Constracts\FeedItemBuilder as FeedItemBuilderConstract;
use Sunra\PhpSimple\HtmlDomParser;

class FeedItemBuilder implements FeedItemBuilderConstract
{
    protected $mappingFields = [];

    protected $originalData;
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
        foreach ($mappingFields as $mapKey => $mapArgs) {
            if (empty($mapArgs)) {
                continue;
            }
            $fieldMapping = new FieldMapping($mapArgs['pattern'], trim($mapKey), $mapArgs["type"]);
            if (isset($mapArgs['group'])) {
                $fieldMapping->addMeta('group', $mapArgs['group']);
            }
            array_push($this->mappingFields, $fieldMapping);
        }
    }

    public function newItem($data)
    {
        $this->originalData = $data["body"];
        if ($this->dataType === 'html') {
            $this->document = @HtmlDomParser::str_get_html($data["body"]);
        }

        $this->feedItem = new FeedItem($data["guid"]);
    }

    public function build()
    {
    }

    public function getFeedItem(): FeedItem
    {
        return $this->feedItem;
    }

    public function getXPathValue($xpath)
    {
    }

    public function getRegexValue($pattern)
    {
    }

    public function getAttributeValue($attribue)
    {
    }
}
