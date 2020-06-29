<?php
namespace Ramphor\Rake\Constracts;

use Monolog\Logger;
use Ramphor\Rake\ProcessResult;

interface Processor
{
    public function execute(): ProcessResult;

    public function writeLog($logMessage, $data = [], $logLevel = Logger::WARNING);
}
