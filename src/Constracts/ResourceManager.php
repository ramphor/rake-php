<?php

namespace Ramphor\Rake\Constracts;

use Ramphor\Rake\ProcessResult;
use Ramphor\Rake\Abstracts\ResourceManager as ResourceManagerAbstract;

interface ResourceManager
{
    public function createFromResult(ProcessResult $result): ResourceManagerAbstract;
}
