<?php
namespace Ramphor\Rake\Managers;

use Ramphor\Rake\Facades\Logger;

class OptionManager
{
    protected $options = [
        'auto_transfer_files' => [
            'value' => true,
            'override' => true,
        ],
    ];

    public function set($optionName, $optionValue, $override = true)
    {
        if (!isset($this->options[$optionName])) {
            $this->options[$optionName] = [
                'value' => $optionValue,
                'override' => $override,
            ];
        } elseif ($this->options[$optionName]['override']) {
            $this->options[$optionName] = [
                'value' => $optionValue,
                'override' => $override,
            ];
        }
    }

    public function get($optionName, $defaultValue = null)
    {
        if (isset($this->options[$optionName]['value'])) {
            $value = $this->options[$optionName]['value'];
            Logger::debug(sprintf('The %s option is return the value is %s', $optionName, $value));
            return $value;
        }

        Logger::debug(sprintf('The %s option is not exists so the default value will be returned', $optionName));
        return $defaultValue;
    }

    public function __call($name, $args)
    {
        if (preg_match('/^is/', $name)) {
            $optionName = preg_replace_callback(['/^is/', '/([A-Z])/'], function ($matches) {
                if (isset($matches[1])) {
                    return sprintf('_%s', strtolower($matches[1]));
                }
            }, $name);
            return $this->get(ltrim($optionName, '_'), false);
        }

        if (!isset($args[0])) {
            $args[1] = true;
        }
        $optionName = preg_replace_callback('/([A-Z])/', function ($matches) {
            if (isset($matches[1])) {
                return sprintf('_%s', strtolower($matches[1]));
            }
        }, $name);
        array_unshift($args, $optionName);
        return call_user_func_array([$this, 'set'], $args);
    }
}
