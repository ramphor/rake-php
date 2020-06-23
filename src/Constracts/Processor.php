<?php
namespace Ramphor\Rake\Constracts;

use Ramphor\Rake\Processor as ProcessorAbstract;
use Ramphor\Rake\ProcessResult;

interface Processor
{
    public function execute(): ProcessResult;

    public function writeLog($logMessage, $data = [], $logLevel = ProcessorAbstract::LOG_WARNING);
}
