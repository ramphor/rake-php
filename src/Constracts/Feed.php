<?php
namespace Ramphor\Rake\Constracts;

use Iterator;
use Resource;

interface Feed
{
    public function setId(string $feedId);

    public function getId();

    public function fetch();

    public function getItems(): Iterator;
}
