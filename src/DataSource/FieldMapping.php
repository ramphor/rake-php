<?php
namespace Ramphor\Rake\DataSource;

class FieldMapping
{
    protected static $supportedSourceTypes = ["xpath", "dom", "regex", "attribute", "guid"];

    protected $sourceField;
    protected $destField;
    protected $sourceType;

    protected $isRequired   = false;
    protected $defaultValue = null;
    protected $callback = null;
    protected $metas        = [];
    protected $callbacks    = [];

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

    public function setSourceType($sourceType)
    {
        $sourceType = strtolower(trim($sourceType));
        if (!in_array($sourceType, self::$supportedSourceTypes)) {
            throw new \Exception("Invalid source type");
        }
        $this->sourceType = $sourceType;
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

    public function createCallbacksFromArray($callbacks)
    {
        if (!is_array($callbacks)) {
            return;
        }
        foreach ($callbacks as $index_or_func => $args_or_func) {
            if (is_string($args_or_func)) {
                if (is_callable($index_or_func)) {
                    $this->addCallback($index_or_func, [$args_or_func]);
                    continue;
                }
                $this->addCallback($args_or_func);
            } elseif (is_array($args_or_func)) {
                if (is_string($index_or_func)) {
                    $this->addCallback($index_or_func, $args_or_func);
                    continue;
                } elseif (is_callable($args_or_func)) {
                    $this->addCallback($args_or_func);
                    continue;
                } elseif (isset($args_or_func['func'])) {
                    if (isset($args_or_func['args'])) {
                        $this->addCallback($args_or_func['func'], $args_or_func['args']);
                        continue;
                    }

                    $callback = $args_or_func['func'];
                    unset($args_or_func['func']);
                    if (empty($args_or_func)) {
                        $this->addCallback($callback);
                    } else {
                        $this->addCallback($callback, $args_or_func);
                    }
                }
            }
        }
    }

    public function addCallback($callback, $args = [])
    {
        if (is_callable($callback)) {
            array_push($this->callbacks, [
                'func' => $callback,
                'args' => $args
            ]);
        }
    }

    protected function buildCallbackArguments($args, $currentValue)
    {
        $argument_index = array_search("%%argument%%", $args);
        if ($argument_index !== false) {
            $args[$argument_index] = $currentValue;
        } else {
            array_unshift($args, $currentValue);
        }

        return $args;
    }

    public function callCallbacks($value)
    {
        if (empty($this->callbacks)) {
            return $value;
        }

        if (is_callable($this->callbacks)) {
            $this->callbacks = [$this->callbacks];
        }

        foreach ($this->callbacks as $callback) {
            $value = call_user_func_array(
                $callback['func'],
                $this->buildCallbackArguments($callback['args'], $value)
            );
        }
        return $value;
    }
}
