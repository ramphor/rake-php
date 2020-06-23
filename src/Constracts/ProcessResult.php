<?php
namespace Ramphor\Rake\Constracts;

use Ramphor\Rake\Resource;

interface ProcessResult
{
    public function getResource(): Resource;
}
