<?php
namespace Ramphor\Rake\Constracts;

use Iterator;
use Resource;

interface Feed
{
    public function fetch();

    public function getItems(): Iterator;
}
