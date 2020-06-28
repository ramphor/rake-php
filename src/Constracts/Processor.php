<?php
namespace Ramphor\Rake\Constracts;

use Ramphor\Rake\Abstracts\AbstractProcessor;
use Ramphor\Rake\ProcessResult;

interface Processor
{
    public function execute(): ProcessResult;

    public function writeLog($logMessage, $data = [], $logLevel = AbstractProcessor::LOG_WARNING);
}
