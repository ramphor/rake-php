<?php
namespace Ramphor\Rake\DataSource;

use Ramphor\Rake\Constracts\FeedItemBuilder as FeedItemBuilderConstract;
use PHPHtmlParser\Dom as Document;

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
        $this->document = new Document();
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
            $mapArgs = $mapArgs + [
                'default_value' => null,
                'required' => false,
            ];
            $fieldMapping = new FieldMapping(
                $mapArgs['pattern'],
                $mapKey,
                $mapArgs['type'],
                $mapArgs['required'],
                $mapArgs['default_value']
            );
            unset($mapArgs['pattern'], $mapArgs['type'], $mapArgs['required'], $mapArgs['default_value']);

            if (!empty($mapArgs)) {
                foreach ($mapArgs as $meta => $value) {
                    $fieldMapping->addMeta($meta, $value);
                }
                unset($mapArgs);
            }
            array_push($this->mappingFields, $fieldMapping);
        }
    }

    public function newItem($data)
    {
        $this->originalData = $data['body'];
        if ($this->dataType === 'html') {
            $this->document->load($data['body']);
        }
        $this->feedItem = new FeedItem($data['guid']);
    }

    public function build()
    {
        if (count($this->mappingFields) <= 0) {
            return;
        }

        foreach ($this->mappingFields as $mappingField) {
            $value = null;
            if (in_array($mappingField->getSourceType(), ['xpath', 'dom'])) {
                $value = $this->getXPathValue(
                    $mappingField->getSource(),
                    $mappingField->getDefaultValue(),
                    $mappingField
                );
            } elseif ($mappingField->getSourceType() == 'regex') {
                $value = $this->getRegexValue(
                    $mappingField->getSource(),
                    $mappingField->getDefaultValue(),
                    $mappingField
                );
            }

            if ($mappingField->isRequired() && is_null($value)) {
                $this->feedItem->deleteGUID();
                break;
            }
        }
    }

    public function getFeedItem(): FeedItem
    {
        return $this->feedItem;
    }

    public function getXPathValue($xpath, $defaultValue, $mappingField)
    {
        if (is_null($this->document)) {
            return $defaultValue;
        }
        foreach ($this->document->find($xpath) as $value) {
            return $value;
        }
    }

    public function getRegexValue($pattern, $defaultValue, $mappingField)
    {
        if (preg_match($pattern, $this->originalData, $matches)) {
            $group = $mappingField->getMeta('group', 0);
            if (isset($matches[$group])) {
                return $matches[$group];
            }
        }
        return $defaultValue;
    }

    public function getAttributeValue($attribue)
    {
    }
}
