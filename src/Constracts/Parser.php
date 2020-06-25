<?php
namespace Ramphor\Rake\Constracts;

use Ramphor\Rake\DataSource\FeedIterator;

interface Parser
{
    public function parse(): FeedIterator;
}
