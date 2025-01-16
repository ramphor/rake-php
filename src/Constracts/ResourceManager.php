<?php

namespace Ramphor\Rake\Constracts;

use Ramphor\Rake\ProcessResult;
use Ramphor\Rake\Resource;
use Ramphor\Rake\Abstracts\ResourceManager as ResourceManagerAbstract;
use Ramphor\Rake\Abstracts\Tooth;

interface ResourceManager
{
    public function createFromResult(ProcessResult $result): ResourceManagerAbstract;

    public function getFilesFromDatabase(Tooth $tooth): ResourceManagerAbstract;

    public function find(int $resouceId): ?Resource;
}
