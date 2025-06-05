<?php

namespace Ramphor\Rake\Constracts;

use Ramphor\Rake\ProcessResult;

interface Processor
{
    /**
     * Execute the processor to get the ProcessResult or null
     *
     * @return \Ramphor\Rake\ProcessResult
     */
    public function process(): ProcessResult;


    public function setImportedId($importedId): self;
}
