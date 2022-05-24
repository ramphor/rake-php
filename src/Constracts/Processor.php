<?php
namespace Ramphor\Rake\Constracts;

interface Processor
{
    /**
     * Execute the processor to get the ProcessResult or null
     *
     * @return \Ramphor\Rake\ProcessResult
     */
    public function execute();
}
