<?php
namespace Ramphor\Rake\DB\Drivers;

use Ramphor\Sql as SqlBuilder;
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

    public function name()
    {
        return self::DRIVER_NAME;
    }

    public function prefix()
    {
        return $this->dbInstance
            ->prefix;
    }

    public function query(SqlBuilder $query)
    {
        return $this->dbInstance
            ->get_results($query);
    }

    public function var(SqlBuilder $query)
    {
        return $this->dbInstance
            ->get_var($query);
    }
}
