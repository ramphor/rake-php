<?php
namespace Ramphor\Rake\Facades;

class DB extends Facade
{
    public function table($name)
    {
        return 'cp_' . $name;
    }
}
