<?php
namespace Rake\Abstracts\Database\Adapter;

use Rake\Contracts\Database\Adapter\DatabaseAdapterInterface;
use Rake\Contracts\Database\DatabaseDriverInterface;


abstract class DatabaseAdapterAbstract implements DatabaseAdapterInterface {
    protected $driver;

    public function __construct(DatabaseDriverInterface $driver) {
        $this->driver = $driver;
    }
}
