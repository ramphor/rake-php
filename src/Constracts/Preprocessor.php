<?php
namespace Ramphor\Rake\Constracts;

interface Preprocessor
{
    public function getId();

    public function setLifeCycle(int $lifeCycle);
}
