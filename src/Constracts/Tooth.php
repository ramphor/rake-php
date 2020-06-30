<?php
namespace Ramphor\Rake\Constracts;

use Iterator;
use Resource;

interface Tooth
{
    public function setId(string $toothId);

    public function getId();

    public function fetch();

    public function getItems(): Iterator;
}
