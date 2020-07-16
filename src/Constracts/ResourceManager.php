<?php

namespace Ramphor\Rake\Constracts;

use Ramphor\Rake\ProcessResult;
use Ramphor\Rake\Resource;
use Ramphor\Rake\Abstracts\ResourceManager as ResourceManagerAbstract;

interface ResourceManager
{
    public function createFromResult(ProcessResult $result): ResourceManagerAbstract;

    public function getFilesFromDatabase(): ResourceManagerAbstract;

    public function find(int $resouceId): ? Resource;
}
