<?php
namespace Ramphor\Rake\Managers;

class OptionManager
{
    protected $options = [
        'auto_transfer_files' => [
            'value' => true,
            'override' => true,
        ],
    ];

    public function register($optionName, $optionValue, $override = true)
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
            return $this->options[$optionName]['value'];
        }

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
        return call_user_func_array([$this, 'register'], $args);
    }
}
