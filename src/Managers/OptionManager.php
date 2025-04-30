<?php

namespace Ramphor\Rake\Managers;

use Ramphor\Rake\Facades\DB;
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

    protected function getFromDB($optionName)
    {
        $query = sql()->select('option_value')->from(DB::table('rake_options'))
            ->where('option_name=?', $optionName)
            ->limit(1);
        $options = DB::var($query);
        if (is_null($options)) {
            return false;
        }

        return unserialize($options);
    }

    public function get($optionName, $defaultValue = null)
    {
        if (isset($this->options[$optionName]['value'])) {
            $value = $this->options[$optionName]['value'];
            Logger::info(sprintf('The %s option is return the value is %s', $optionName, $value));
            return $value;
        }

        if (!($value = $this->getFromDB($optionName))) {
            Logger::info(sprintf('The %s option is not exists so the default value will be returned', $optionName));
            return $defaultValue;
        }

        // Return the options from database
        return $value;
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

    public function loadAllOptions()
    {
        $query = sql()->select('*')->from(DB::table('rake_options'))->where('autoload=?', 1);
        $options = DB::get($query);
        if (is_array($options)) {
            foreach ($options as $option) {
                $this->set($option->option_name, unserialize($option->option_value)) ;
            }
        }
    }

    public function checkExists($optionName)
    {
        $query = sql()->select('id')->from(DB::table('rake_options'))
            ->where('option_name=?', $optionName);

        return (int) DB::var($query);
    }

    public function update($optionName, $optionValue, $autoload = false)
    {
        $optionId = $this->checkExists($optionName);
        $query    = sql();
        if ($optionId > 0) {
            $query = $query->update(DB::table('rake_options'))
                ->set([
                    'option_value' => serialize($optionValue),
                    'autoload'     => (int)$autoload
                ])
                ->where('id=?', $optionId);
        } else {
            $query = $query->insertInto(DB::table('rake_options'), ['option_name', 'option_value', 'autoload'])
                ->values(
                    '?, ?, ?',
                    $optionName,
                    serialize($optionValue),
                    (int)$autoload
                );
        }

        // Override current option values
        $this->set($optionName, $optionValue);

        return DB::query($query);
    }
}
