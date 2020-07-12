<?php
namespace Ramphor\Rake\DB\Drivers;

use Ramphor\Rake\Abstracts\Driver;
use Ramphor\Rake\Constracts\SQLQueryBuilder;

class WordPress extends Driver
{
    public const DRIVER_NAME = 'wordpress';

    protected $dbInstance;

    public function __construct()
    {
        $this->dbInstance = $GLOBALS['wpdb'];
    }

    public function getName()
    {
        return self::DRIVER_NAME;
    }

    public function getPrefix() {
        return $this->dbInstance->prefix;
    }

    public function query(SQLQueryBuilder $query)
    {
        return $this->dbInstance->query((string) $query);
    }
}
