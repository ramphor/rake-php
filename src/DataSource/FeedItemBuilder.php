<?php
namespace Ramphor\Rake\DataSource;

use Ramphor\Rake\Constracts\FeedItemBuilder as FeedItemBuilderConstract;
use Ramphor\Rake\Facades\Document;

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

            if (isset($mapArgs['callbacks'])) {
                $fieldMapping->createCallbacksFromArray($mapArgs['callbacks']);
            }

            unset(
                $mapArgs['pattern'],
                $mapArgs['type'],
                $mapArgs['required'],
                $mapArgs['default_value'],
                $mapArgs['callbacks']
            );

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
        $this->feedItem = new FeedItem($data['guid'], isset($data['urlID']) ? $data['urlID'] : null);

        if (empty($data['skipped'])) {
            if ($this->dataType === 'html') {
                $this->document = Document::load($data['body']);
            }
        } else {
            $this->feedItem->setSkipped();
        }
    }

    public function build()
    {
        if (count($this->mappingFields) <= 0) {
            return;
        }

        if ($this->feedItem->isSkipped()) {
            $this->feedItem->deleteGuid();
            return;
        }

        foreach ($this->mappingFields as $mappingField) {
            $value = null;
            if (in_array($mappingField->getSourceType(), ['xpath', 'dom'])) {
                $value = $this->getXPathValue(
                    $mappingField->getSource(),
                    $mappingField
                );
            } elseif ($mappingField->getSourceType() == 'regex') {
                $value = $this->getRegexValue(
                    $mappingField->getSource(),
                    $mappingField
                );
            }

            if ($mappingField->isRequired() && is_null($value)) {
                $this->feedItem->deleteGuid();
                break;
            }

            $this->feedItem->setProperty(
                $mappingField->getDestination(),
                $mappingField->callCallbacks($value)
            );
        }
    }

    public function getFeedItem(): FeedItem
    {
        return $this->feedItem;
    }

    public function methodTransformer($method)
    {
        $method = strtolower($method);
        if ($method === 'innerhtml') {
            return 'innerHtml';
        }
        if ($method === 'outerhtml') {
            return 'outerHtml';
        }
        return $method;
    }

    public function getXPathValue($xpath, $mappingField)
    {
        if (is_null($this->document)) {
            return $mappingField->getDefaultValue();
        }
        $elements = $this->document->find($xpath);
        if (count($elements) <= 0) {
            return $mappingField->getDefaultValue();
        }
        $get = $this->methodTransformer($mappingField->getMeta('get', 'text'));
        if (!in_array($get, ['text', 'attribute', 'innerHtml', 'outerHtml'])) {
            // Will show warning later

            // Override get type value
            $get = 'text';
        }

        $return = $mappingField->getMeta('return', 'field');
        if (!in_array($return, ['field', 'fields', 'array'])) {
            // Will show warning later

            // Override return type value
            $return = 'field';
        }

        if ($return == 'field') {
            return $this->getValueFromDomElement($elements[0], $get, $mappingField);
        }

        $values = [];
        foreach ($elements as $element) {
            array_push($values, $this->getValueFromDomElement($element, $get, $mappingField));
        }
        return $values;
    }

    public function getValueFromDomElement($element, $content_type, $mappingField)
    {
        if ($content_type !== 'attribute') {
            return $element->$content_type;
        }
        $attribue = $mappingField->getMeta('attribute', null);
        if (empty($attribue)) {
            return $attribue->getDefaultValue();
        }
        return $element->getAttribute($attribue);
    }

    public function getRegexValue($pattern, $mappingField)
    {
        if (preg_match($pattern, $this->originalData, $matches)) {
            $group = $mappingField->getMeta('group', 0);
            if (isset($matches[$group])) {
                return $matches[$group];
            }
        }
        return $mappingField->getDefaultValue();
    }

    public function getAttributeValue($attribue)
    {
    }
}
