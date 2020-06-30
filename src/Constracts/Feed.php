<?php
namespace Ramphor\Rake\Constracts;

interface Feed
{
    public function getId();

    public function setLifeCycle(int $lifeCycle);

    public function execute();
}
