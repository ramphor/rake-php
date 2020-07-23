<?php
namespace Ramphor\Rake\Constracts;

interface Feed
{
    public function execute();

    public function valid();

    public function next();

    public function rewind();
}
