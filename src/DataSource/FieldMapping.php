<?php
namespace Ramphor\Rake\DataSource;

class FieldMapping
{
    protected $sourceField;
    protected $destField;
    protected $sourceType;

    protected $isRequired   = false;
    protected $defaultValue = null;
    protected $metas        = [];

    protected $supportedSourceTypes = ["xpath", "dom", "regex", "attribute"];

    public function __construct($sourceField, $destField, $sourceType, $isRequired = false, $defaultValue = null)
    {
        $this->setSource($sourceField);
        $this->setDestination($destField);
        $this->setSourceType($sourceType);
        $this->setRequired($isRequired);
        $this->setDefaultValue($defaultValue);
    }

    public function setSource($sourceField)
    {
        $this->sourceField = trim($sourceField);
    }

    public function getSource()
    {
        return $this->sourceField;
    }

    public function setDestination($destField)
    {
        $this->destField = trim($destField);
    }

    public function getDestination()
    {
        return $this->destField;
    }

    public function setSourceType($souceType)
    {
        $souceType = strtolower(trim($souceType));
        if (!in_array($souceType, $this->supportedSourceTypes)) {
            throw new \Exception("Invalid resource type");
        }
        $this->sourceType = $souceType;
    }

    public function getSourceType()
    {
        return $this->sourceType;
    }

    public function setRequired($isRequired)
    {
        $this->isRequired = (bool) $isRequired;
    }

    public function isRequired()
    {
        return $this->isRequired;
    }

    public function setDefaultValue($defaultValue)
    {
        $this->defaultValue = $defaultValue;
    }

    public function getDefaultValue()
    {
        return $this->defaultValue;
    }

    public function addMeta($metaKey, $metaValue)
    {
        $metaKey = trim($metaKey);
        if (isset($this->metas[$metaKey])) {
            return;
        }
        $this->metas[$metaKey] = $metaValue;
    }

    public function getMeta($metaKey, $defaultValue)
    {
        if (isset($this->metas[$metaKey])) {
            return $this->metas[$metaKey];
        }
        return $defaultValue;
    }
}
