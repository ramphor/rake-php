<?php
namespace Ramphor\Rake\Abstracts;

use Ramphor\Rake\Constracts\Driver as DriverConstract;

abstract class Driver implements DriverConstract
{
    public function generateHashFromURL($url)
    {
    }
}
