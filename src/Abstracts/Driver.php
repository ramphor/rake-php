<?php
namespace Ramphor\Rake\Abstracts;

use Ramphor\Rake\Constracts\Driver as DriverConstract;

abstract class Driver implements DriverConstract
{
    public function table($name)
    {
        return sprintf('%s%s', $this->prefix(), $name);
    }
}
