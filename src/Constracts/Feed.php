<?php
namespace Ramphor\Rake\Constracts;

interface Feed
{
    public function execute();

    public function validate();

    public function next();

    public function rewind();
}
