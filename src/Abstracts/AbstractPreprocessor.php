<?php
namespace Ramphor\Rake\Abstracts;

use Ramphor\Rake\Contracts\Preprocessor;
use Ramphor\Rake\Abstracts\AbstractProcessor;

abstract class AbstractPreprocessor implements Preprocessor
{
    const LIFE_CYCLE_ONE_TIME = 1;

    protected $lifeCycle;

    public function setLifeCycle()
    {
    }

    public function writeLog($logMessage, $data = [], $logLevel = AbstractProcessor::LOG_WARNING)
    {
    }
}
