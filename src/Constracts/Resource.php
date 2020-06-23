<?php
namespace Ramphor\Rake\Constracts;

interface Resource
{
    public function isMapped();

    public function import();
}
