<?php
namespace Ramphor\Rake\DataSource;

class FieldMapping
{
    protected $sourceField;
    protected $destField;
    protected $isRequired   = false;
    protected $defaultValue = null;

    public function __construct($sourceField = null, $destField = null, $isRequired = false, $defaultValue = null)
    {
        if (! is_null($sourceField)) {
            $this->setSource($sourceField);
        }

        if (! is_null($destField)) {
            $this->setDestination($destField);
        }
        $this->setRequired($isRequired);
        $this->setDefaultValue($defaultValue);
    }

    public function setSource($sourceField)
    {
        $this->sourceField = $sourceField;
    }

    public function setDestination($destField)
    {
        $this->destField = $destField;
    }

    public function setRequired($isRequired)
    {
        $this->isRequired = (bool) $isRequired;
    }

    public function setDefaultValue($defaultValue)
    {
        $this->defaultValue = $defaultValue;
    }
}
