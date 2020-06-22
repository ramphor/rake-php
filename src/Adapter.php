<?php
namespace Ramphor\Rake;

use Ramphor\Rake\Constracts\Adapter as AdapterConstract;

abstract class Adapter implements AdapterConstract
{
    public function generateHashFromURL($url)
    {
    }
}
