<?php

namespace Ramphor\Rake\Constracts;

interface Feed
{
    public function get_name();

    public function execute();

    public function valid();

    public function next();

    public function rewind();
}
