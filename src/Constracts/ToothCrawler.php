<?php

namespace Ramphor\Rake\Constracts;

interface ToothCrawler extends Tooth
{
    public function validateURL($url);
}
