<?php

namespace Ramphor\Rake\DataSource;

use PHPHtmlParser\Dom as Document;
use Ramphor\Rake\Constracts\FeedItemBuilder as FeedItemBuilderConstract;
use Ramphor\Rake\Facades\Logger;

class FeedItemBuilder implements FeedItemBuilderConstract
{
    /**
     * @var \Ramphor\Rake\DataSource\FieldMapping[]
     */
    protected $mappingFields = [];

    protected $guid;
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
            if (empty($mapArgs['type'])) {
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

            // Setup callbacks
            if (isset($mapArgs['callback']) && is_callable($mapArgs['callback'])) {
                $fieldMapping->addCallback($mapArgs['callback']);
            }
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
        $this->document     = null;
        // $data['body'] is exists when call `append()` method of Response object

        // Set GUID for feed item builder
        if (isset($data['guid'])) {
            $this->setGuid($data['guid']);
        }


        if (isset($data['body'])) {
            $this->feedItem     =  new FeedItem($data['guid'], isset($data['urlID']) ? $data['urlID'] : null);
            $this->originalData = $data['body'];
            if (isset($data['status']) && $data['status'] === 'success') {
                if ($this->dataType === 'html') {
                    $this->document = new Document();

                    // Create HTML Document from original HTML
                    $this->document->loadStr((string)$data['body']);


                    $linkInHtml = [];
                    $links = $this->document->find('a');
                    foreach ($links as $link) {
                        $href = $link->getAttribute('href');
                        if (is_null($link) || strpos($href, '#') === 0 || empty(trim($link))) {
                            continue;
                        }
                        if (strpos($href, '/') === false) {
                            // unexpected cases
                            if (strlen($href) < 4) {
                                continue;
                            }

                            // check href must start with http or https. exclude tel, mailto
                            $protocol = substr($href, 0, 4);
                            if ($protocol !== 'http') {
                                continue;
                            }
                        }
                        $linkInHtml[] = trim($href);
                    }
                    $this->feedItem->setHtmlLinks($linkInHtml);
                }
            } else {
                $this->feedItem->setError($data['status']);
            }
        } else {
            $this->feedItem = new FeedItem();
            $this->originalData = $data;
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
            } elseif ($mappingField->getSourceType() === 'regex') {
                $value = $this->getRegexValue(
                    $mappingField->getSource(),
                    $mappingField
                );
            } elseif ($mappingField->getSourceType() === 'attribute') {
                $value = $this->getAttributeValue(
                    $mappingField->getSource(),
                    $mappingField
                );
            } elseif ($mappingField->getSourceType() === 'custom') {
                $value = $this->getCustomValue(
                    $mappingField->getSource(),
                    $mappingField
                );
            } elseif ($mappingField->getSourceType() === 'guid') {
                $value = $this->getGuidValue(
                    $mappingField->getSource(),
                    $mappingField
                );
            }

            $this->feedItem->setProperty(
                $mappingField->getDestination(),
                $mappingField->callCallbacks($value, $this->feedItem, $this->originalData)
            );

            if ($mappingField->isRequired() && is_null($value)) {
                $this->feedItem->deleteGuid();
            }
        }
    }

    public function getFeedItem(): FeedItem
    {
        return $this->feedItem;
    }

    public function methodTransformer($method)
    {
        $method = strtolower($method);
        if ($method === 'html') {
            return 'innerHtml';
        }
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
        $get        = $this->methodTransformer($mappingField->getMeta('get', 'text'));
        $allowProps = ['text', 'attribute', 'innerHtml', 'outerHtml', 'dom_object'];
        if (!in_array($get, $allowProps)) {
            Logger::warning(sprintf('Mapping field call the invalid property: %s', var_export([
                'called' => $get,
                'allow_props' => $allowProps,
            ], true)));

            // Override get type value
            $get = 'text';
        }

        $return = $mappingField->getMeta('return', 'field');
        if (!in_array($return, ['field', 'fields', 'array'])) {
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
        if ($content_type === 'dom_object') {
            return $element;
        }
        if ($content_type !== 'attribute') {
            return $element->$content_type;
        }
        $attribue = $mappingField->getMeta('attribute', null);
        if (empty($attribue)) {
            return $attribue->getDefaultValue();
        }
        return $element->getAttribute((string)$attribue);
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

    public function getAttributeValue($attribute, $mappingField)
    {
        $attributeKeys = explode('.', $attribute);
        $value         = $this->originalData;

        foreach ($attributeKeys as $attributeKey) {
            if (is_array($value)) {
                if (!isset($value[$attributeKey])) {
                    return $mappingField->getDefaultValue();
                }
                $value = $value[$attributeKey];
            } elseif (is_object($value)) {
                if (!isset($value->$attributeKey)) {
                    return $mappingField->getDefaultValue();
                }
                $value = $value->$attributeKey;
            } else {
                return $mappingField->getDefaultValue();
            }
        }

        return $value;
    }

    public function setGuid($guid)
    {
        $this->guid = $guid;
    }

    /**
     * @var \Ramphor\Rake\DataSource\FieldMapping $mappingField
     */
    public function getCustomValue($attribute, $mappingField)
    {
        return $mappingField->getDefaultValue();
    }


    public function getGuidValue($attribute, $mappingField)
    {
        return $this->guid;
    }
}
